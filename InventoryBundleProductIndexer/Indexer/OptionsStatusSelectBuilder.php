<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

class OptionsStatusSelectBuilder
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param InventoryConfigurationInterface $inventoryConfiguration
     * @param ReservationsIndexTable $reservationsIndexTable
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly IndexNameBuilder $indexNameBuilder,
        private readonly IndexNameResolverInterface $indexNameResolver,
        private readonly MetadataPool $metadataPool,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly InventoryConfigurationInterface $inventoryConfiguration,
        private readonly ReservationsIndexTable $reservationsIndexTable,
    ) {
    }

    /**
     * Build bundle options stock status select
     *
     * @param int $stockId
     * @param string[] $skuList
     * @param IndexAlias $indexAlias
     * @return Select
     */
    public function execute(int $stockId, array $skuList = [], IndexAlias $indexAlias = IndexAlias::MAIN): Select
    {
        $indexName = $this->indexNameBuilder->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string) $stockId)
            ->setAlias($indexAlias->value)
            ->build();
        $indexTableName = $this->indexNameResolver->resolveName($indexName);
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $productLinkField = $metadata->getLinkField();
        $reservationsTableName = $this->reservationsIndexTable->getTableName($stockId);

        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                ['stock' => $indexTableName],
                []
            )->joinInner(
                ['product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'product_entity.sku = stock.sku',
                []
            )->joinInner(
                ['bundle_selection' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
                'bundle_selection.product_id = product_entity.entity_id',
                []
            )->joinInner(
                ['bundle_option' => $this->resourceConnection->getTableName('catalog_product_bundle_option')],
                'bundle_option.option_id = bundle_selection.option_id',
                []
            )->joinLeft(
                ['stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'stock_item.product_id = product_entity.entity_id'
                . ' AND stock_item.stock_id = ' . $this->defaultStockProvider->getId(),
                []
            )->joinInner(
                ['parent_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'parent_product_entity.' . $productLinkField . ' = bundle_option.parent_id',
                []
            )->joinLeft(
                ['reservations' => $this->resourceConnection->getTableName($reservationsTableName)],
                'reservations.sku = stock.sku',
                []
            )->group(
                ['bundle_option.parent_id', 'bundle_option.option_id']
            )->columns(
                [
                    'sku' => 'parent_product_entity.sku',
                    'option_id' => 'bundle_option.option_id',
                    'required' => 'bundle_option.required',
                    'quantity' => new \Zend_Db_Expr('SUM(stock.quantity)'),
                    'stock_status' => $this->getOptionsStatusExpression(),
                ]
            );

        if (!empty($skuList)) {
            $select->where('parent_product_entity.sku IN (?)', $skuList);
        }

        return $select;
    }

    /**
     * Build expression for bundle options stock status
     *
     * @return \Zend_Db_Expr
     */
    private function getOptionsStatusExpression(): \Zend_Db_Expr
    {
        $connection = $this->resourceConnection->getConnection();

        $reservationQty = $connection->getIfNullSql('reservations.reservation_qty');
        $quantity = '(stock.quantity - stock_item.min_qty + ' . $reservationQty . ')';
        $isAvailableExpr = $connection->getCheckSql(
            'bundle_selection.selection_can_change_qty = 0 AND bundle_selection.selection_qty > ' . $quantity,
            '0',
            'stock.is_salable'
        );

        if ($this->inventoryConfiguration->getBackorders()) {
            $backordersExpr = $connection->getCheckSql(
                'stock_item.use_config_backorders = 0 AND stock_item.backorders = 0',
                $isAvailableExpr,
                'stock.is_salable'
            );
        } else {
            $backordersExpr = $connection->getCheckSql(
                'stock_item.use_config_backorders = 0 AND stock_item.backorders > 0',
                'stock.is_salable',
                $isAvailableExpr
            );
        }

        if ($this->inventoryConfiguration->getManageStock()) {
            $statusExpr = $connection->getCheckSql(
                'stock_item.use_config_manage_stock = 0 AND stock_item.manage_stock = 0',
                1,
                $backordersExpr
            );
        } else {
            $statusExpr = $connection->getCheckSql(
                'stock_item.use_config_manage_stock = 0 AND stock_item.manage_stock = 1',
                $backordersExpr,
                1
            );
        }

        return new \Zend_Db_Expr('MAX(' . $statusExpr . ')');
    }
}
