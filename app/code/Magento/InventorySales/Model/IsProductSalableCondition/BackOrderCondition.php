<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * @inheritdoc
 */
class BackOrderCondition implements IsProductSalableInterface
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var CollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param CollectionFactory $sourceItemCollectionFactory
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        GetSourceConfigurationInterface $getSourceConfiguration,
        CollectionFactory $sourceItemCollectionFactory
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $minQty = $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        //todo; Temporal solution. Should be reworked.
        $sourceItemCollection = $this->sourceItemCollectionFactory->create();
        $sourceItemCollection->addFieldToSelect('source_code');
        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->getSelect()->join(
            ['link' => $sourceItemCollection->getTable('inventory_source_stock_link')],
            'main_table.source_code = link.source_code',
            []
        )->where('link.stock_id = ?', $stockId);
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $globalBackOrders = $globalSourceConfiguration->getBackorders();
        $result = $globalBackOrders !== SourceItemConfigurationInterface::BACKORDERS_NO && $minQty >= 0;
        foreach ($sourceItemCollection as $sourceItem) {
            $backOrders = $this->getBackorders($sku, $sourceItem, $globalSourceConfiguration);
            if ($backOrders !== SourceItemConfigurationInterface::BACKORDERS_NO && $minQty >= 0) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $sku
     * @param SourceItemInterface $sourceItem
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @return int
     */
    private function getBackorders(
        string $sku,
        SourceItemInterface $sourceItem,
        SourceItemConfigurationInterface $globalConfiguration
    ): int {
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceItem->getSourceCode());
        $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceItem->getSourceCode());

        $defaultValue = $sourceConfiguration->getBackorders() !== null
            ? $sourceConfiguration->getBackorders()
            : $globalConfiguration->getBackorders();

        return $sourceItemConfiguration->getBackorders() !== null
            ? $sourceItemConfiguration->getBackorders()
            : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return float|null
     */
    private function getMinQty(
        StockItemConfigurationInterface $globalConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ) {
        $defaultValue = $stockConfiguration->getMinQty() !== null
            ? $stockConfiguration->getMinQty()
            : $globalConfiguration->getMinQty();

        return $stockItemConfiguration->getMinQty() !== null ? $stockItemConfiguration->getMinQty() : $defaultValue;
    }
}
