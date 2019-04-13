<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Add Stock data to collection
 */
class AddStockDataToCollection
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockConfigurationInterface $configuration
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider = null,
        StockConfigurationInterface $configuration
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider ?: ObjectManager::getInstance()
            ->get(DefaultStockProviderInterface::class);
        $this->stockConfiguration = $configuration;
    }

    /**
     * @param Collection $collection
     * @param bool $isFilterInStock
     * @param int $stockId
     * @return void
     */
    public function execute(Collection $collection, bool $isFilterInStock, int $stockId)
    {
        if ($stockId === $this->defaultStockProvider->getId()) {
            $isSalableColumnName = 'stock_status';

            $resource = $collection->getResource();
            $collection->getSelect()
                ->join(
                    ['stock_status_index' => $resource->getTable('cataloginventory_stock_status')],
                    sprintf('%s.entity_id = stock_status_index.product_id', Collection::MAIN_TABLE_ALIAS),
                    [IndexStructure::IS_SALABLE => $isSalableColumnName]
                );
        } else {
            $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stockId);
            $resource = $collection->getResource();
            $collection->getSelect()->join(
                ['product' => $resource->getTable('catalog_product_entity')],
                sprintf('product.entity_id = %s.entity_id', Collection::MAIN_TABLE_ALIAS),
                []
            );
            $isSalableColumnName = IndexStructure::IS_SALABLE;
            $collection->getSelect()
                ->joinLeft(
                    ['stock_status_index' => $stockIndexTableName],
                    'product.sku = stock_status_index.' . IndexStructure::SKU,
                    [$isSalableColumnName]
                );
        }

        if ($isFilterInStock) {
            $globalManageStock = (int)$this->stockConfiguration->getManageStock();
            $stockSettingsCondition = '(legacy_stock_item.use_config_manage_stock = 0 AND legacy_stock_item.manage_stock = 0)';
            if (0 === $globalManageStock) {
                $stockSettingsCondition .= ' OR legacy_stock_item.use_config_manage_stock = 1';
            }
            $collection->getSelect()
                ->join(
                    ['legacy_stock_item' => $resource->getTable('cataloginventory_stock_item')],
                    sprintf('%s.entity_id = legacy_stock_item.product_id', Collection::MAIN_TABLE_ALIAS),
                    ['use_config_manage_stock', 'manage_stock']
                );
            $collection->getSelect()
                ->where('(stock_status_index.' . $isSalableColumnName . ' = ?) OR (' . $stockSettingsCondition . ')', 1);
        }
    }
}
