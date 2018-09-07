<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
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
    private $getStockItemConfiguration;

    /**
     * @var CollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param CollectionFactory $sourceItemCollectionFactory
     */
    public function __construct(
        GetStockConfigurationInterface $getStockItemConfiguration,
        GetSourceConfigurationInterface $getSourceConfiguration,
        CollectionFactory $sourceItemCollectionFactory
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->forStockItem($sku, $stockId);

        //todo; Temporal solution. Should be reworked.
        $sourceItemCollection = $this->sourceItemCollectionFactory->create();
        $sourceItemCollection->addFieldToSelect('source_code');
        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->getSelect()->join(
            ['link' => $sourceItemCollection->getTable('inventory_source_stock_link')],
            'main_table.source_code = link.source_code',
            []
        )->where('link.stock_id = ?', $stockId);

        foreach ($sourceItemCollection as $sourceItem) {
            $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceItem->getSourceCode());
            if ($sourceItemConfiguration->getBackorders() !== SourceItemConfigurationInterface::BACKORDERS_NO
                && $stockItemConfiguration->getMinQty() >= 0) {
                return true;
            }
        }

        return false;
    }
}
