<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Pricing\Price\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\PopulateIndexTableInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Populate index table with data from select
 */
class PopulateOptionsIndexTable implements PopulateIndexTableInterface
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var PopulateIndexTableInterface
     */
    private $populateIndexTable;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockConfigurationInterface $stockConfig
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param PopulateIndexTableInterface $populateIndexTable
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockConfigurationInterface $stockConfig,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        PopulateIndexTableInterface $populateIndexTable,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockConfig = $stockConfig;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->populateIndexTable = $populateIndexTable;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function execute(Select $select, string $indexTableName): void
    {
        if ($this->stockConfig->isShowOutOfStock()) {
            $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $entityTableName = $metadata->getEntityTable();
            $entityIdField = $metadata->getIdentifierField();
            $stocks = [];
            foreach ($this->storeManager->getWebsites() as $website) {
                $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
                $stocks[(int) $stock->getStockId()][] = (int) $website->getId();
            }
            $defaultStockId = $this->defaultStockProvider->getId();
            $stocksCount = count($stocks);
            foreach ($stocks as $stockId => $websiteIds) {
                $selectClone = clone $select;
                if ($stockId === $defaultStockId) {
                    $stockIndexTableName = $this->resourceConnection->getTableName('cataloginventory_stock_status');
                    $selectClone->joinInner(
                        ['child_stock_default' => $stockIndexTableName],
                        'child_stock_default.product_id = l.product_id',
                        []
                    )->joinInner(
                        ['parent_stock_default' => $stockIndexTableName],
                        'parent_stock_default.product_id = le.' . $entityIdField,
                        []
                    )->where(
                        'child_stock_default.stock_status = 1 OR parent_stock_default.stock_status = 0'
                    );
                } else {
                    $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stockId);
                    $selectClone->joinInner(
                        ['child_entity' => $entityTableName],
                        'child_entity.' . $entityIdField . ' = l.product_id',
                        []
                    )->joinInner(
                        ['child_stock' => $stockIndexTableName],
                        'child_stock.sku = child_entity.sku',
                        []
                    )->joinInner(
                        ['parent_stock' => $stockIndexTableName],
                        'parent_stock.sku = le.sku',
                        []
                    )->where(
                        'child_stock.is_salable = 1 OR parent_stock.is_salable = 0'
                    );
                }

                if ($stocksCount > 1) {
                    $selectClone->where(
                        'i.website_id IN (?)',
                        $websiteIds
                    );
                }

                $this->populateIndexTable->execute($selectClone, $indexTableName);
            }
        } else {
            $this->populateIndexTable->execute($select, $indexTableName);
        }
    }
}
