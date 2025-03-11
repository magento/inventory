<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

class OptionsStatusSelectBuilder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var InventoryConfigurationInterface
     */
    private $inventoryConfiguration;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param InventoryConfigurationInterface $inventoryConfiguration
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool,
        DefaultStockProviderInterface $defaultStockProvider,
        InventoryConfigurationInterface $inventoryConfiguration
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->inventoryConfiguration = $inventoryConfiguration;
    }

    /**
     * Build bundle options stock status select
     *
     * @param int $stockId
     * @param array $skuList
     * @return Select
     */
    public function execute(int $stockId, array $skuList = []): Select
    {
        $indexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string) $stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();
        $indexTableName = $this->indexNameResolver->resolveName($indexName);

        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $productLinkField = $metadata->getLinkField();

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
        $isAvailableExpr = $connection->getCheckSql(
            'bundle_selection.selection_can_change_qty = 0 AND bundle_selection.selection_qty > stock.quantity',
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
