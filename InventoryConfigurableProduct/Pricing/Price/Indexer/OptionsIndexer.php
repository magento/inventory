<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Pricing\Price\Indexer;

use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\OptionsIndexerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\OptionsSelectBuilderInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Configurable product options prices aggregator
 */
class OptionsIndexer implements OptionsIndexerInterface
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
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OptionsSelectBuilderInterface
     */
    private $optionsSelectBuilder;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockConfigurationInterface $stockConfig
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ResourceConnection $resourceConnection
     * @param OptionsSelectBuilderInterface $optionsSelectBuilder
     * @param TableMaintainer $tableMaintainer
     * @param string $connectionName
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockConfigurationInterface $stockConfig,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        ResourceConnection $resourceConnection,
        OptionsSelectBuilderInterface $optionsSelectBuilder,
        TableMaintainer $tableMaintainer,
        string $connectionName = 'indexer'
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockConfig = $stockConfig;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->resourceConnection = $resourceConnection;
        $this->optionsSelectBuilder = $optionsSelectBuilder;
        $this->tableMaintainer = $tableMaintainer;
        $this->connectionName = $connectionName;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $indexTable, string $tempIndexTable, ?array $entityIds = null): void
    {
        $select = $this->optionsSelectBuilder->execute($indexTable, $entityIds);
        if ($this->stockConfig->isShowOutOfStock()) {
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
                        'parent_stock_default.product_id = le.entity_id',
                        []
                    )->where(
                        'child_stock_default.stock_status = 1 OR parent_stock_default.stock_status = 0'
                    );
                } else {
                    $selectClone->joinInner(
                        ['child_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                        'child_entity.entity_id = l.product_id',
                        []
                    )->joinInner(
                        ['child_stock' => $this->stockIndexTableNameResolver->execute($stockId)],
                        'child_stock.sku = child_entity.sku',
                        []
                    )->joinInner(
                        ['parent_stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                        'parent_stock_item.product_id = le.entity_id',
                        []
                    )->where(
                        'child_stock.is_salable = 1'
                        . ' OR parent_stock_item.is_in_stock = 0'
                        . ' OR NOT EXISTS ('. $this->getInStockOptionsSelect($indexTable, $stockId)->assemble() .')'
                    );
                }

                if ($stocksCount > 1) {
                    $selectClone->where(
                        'i.website_id IN (?)',
                        $websiteIds
                    );
                }

                $this->tableMaintainer->insertFromSelect($selectClone, $tempIndexTable, []);
            }
        } else {
            $this->tableMaintainer->insertFromSelect($select, $tempIndexTable, []);
        }
    }

    /**
     * Return select with in-stock configurable product options
     *
     * The indexed stock status of configurable product does not take into account disabled products
     * This select is intended only to check if any active option of the configurable product is in stock
     *
     * @param string $indexTableName
     * @param int $stockId
     */
    public function getInStockOptionsSelect(string $indexTableName, int $stockId): Select
    {
        $select = $this->resourceConnection->getConnection($this->connectionName)->select();

        $select->from(
            ['i_2' => $indexTableName],
            [new Expression('1')]
        )->joinInner(
            ['l_2' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            'l_2.product_id = i_2.entity_id',
            []
        )->joinInner(
            ['ce_2' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'ce_2.entity_id = l_2.product_id',
            []
        )->joinInner(
            ['cs_2' => $this->stockIndexTableNameResolver->execute($stockId)],
            'cs_2.sku = ce_2.sku',
            []
        )->where(
            'l_2.parent_id = l.parent_id' .
            ' AND i_2.website_id = i.website_id' .
            ' AND cs_2.is_salable = 1'
        );

        return $select;
    }
}
