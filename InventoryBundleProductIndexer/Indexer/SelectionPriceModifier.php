<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Bundle\Model\ResourceModel\Indexer\SelectionPriceModifierInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

class SelectionPriceModifier implements SelectionPriceModifierInterface
{
    /**
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockTableResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockConfigurationInterface $stockConfiguration
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param string $connectionName
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StockIndexTableNameResolverInterface $stockTableResolver,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly StoreManagerInterface $storeManager,
        private readonly StockResolverInterface $stockResolver,
        private readonly string $connectionName = 'indexer'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function modify(string $indexTable, array $dimensions): void
    {
        if (!$this->stockConfiguration->isShowOutOfStock()) {
            return;
        }
        
        $connection = $this->resource->getConnection($this->connectionName);
        
        $stocks = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($dimensions as $dimension) {
            if ($dimension->getName() === WebsiteDimensionProvider::DIMENSION_NAME && $dimension->getValue()) {
                $websites = [$this->storeManager->getWebsite((int) $dimension->getValue())];
            }
        }
        foreach ($websites as $website) {
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
            $stocks[(int) $stock->getStockId()][$website->getId()] = (int) $website->getId();
        }
        
        $defaultStockId = $this->defaultStockProvider->getId();
        
        foreach ($stocks as $stockId => $websiteIds) {
            $select = $connection->select()
                ->from(['i' => $indexTable])
                ->joinInner(
                    ['selection' => $this->getTable('catalog_product_bundle_selection')],
                    "selection.selection_id = i.selection_id",
                    []
                );
            if ($stockId === $defaultStockId) {
                $stockIndexTableName = $this->getTable('cataloginventory_stock_status');
                $select->joinInner(
                    ['child_stock' => $stockIndexTableName],
                    'child_stock.product_id = selection.product_id',
                    []
                )->joinInner(
                    ['parent_stock' => $stockIndexTableName],
                    'parent_stock.product_id = i.entity_id',
                    []
                )->where(
                    'parent_stock.stock_status = 1'
                )->where(
                    'child_stock.stock_status = 0'
                );
            } else {
                $stockIndexTableName = $this->stockTableResolver->execute($stockId);
                $select->joinInner(
                    ['parent_product_entity' => $this->getTable('catalog_product_entity')],
                    'parent_product_entity.entity_id = i.entity_id',
                    []
                )->joinInner(
                    ['child_product_entity' => $this->getTable('catalog_product_entity')],
                    'child_product_entity.entity_id = selection.product_id',
                    []
                )->joinInner(
                    ['child_stock' => $stockIndexTableName],
                    'child_stock.sku = child_product_entity.sku',
                    []
                )->joinInner(
                    ['parent_stock' => $stockIndexTableName],
                    'parent_stock.sku = parent_product_entity.sku',
                    []
                )->where(
                    'parent_stock.is_salable = 1'
                )->where(
                    'child_stock.is_salable = 0'
                );
            }
            $select->where('i.website_id IN (?)', $websiteIds);
            $connection->query($connection->deleteFromSelect($select, 'i'));
        }
    }

    /**
     * Returns fully qualified table name
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName): string
    {
        return $this->resource->getTableName($tableName, $this->connectionName);
    }
}
