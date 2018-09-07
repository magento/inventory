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
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * @inheritdoc
 */
class BackOrderNotifyCustomerCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var CollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param CollectionFactory $sourceItemCollectionFactory
     */
    public function __construct(
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        GetStockItemDataInterface $getStockItemData,
        GetSourceConfigurationInterface $getSourceConfiguration,
        CollectionFactory $sourceItemCollectionFactory
    ) {
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
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
        foreach ($sourceItemCollection as $sourceItem) {
            $backorders = $this->getBackorders($sku, $sourceItem, $globalSourceConfiguration);
            if ($backorders === SourceItemConfigurationInterface::BACKORDERS_YES_NOTIFY) {
                $stockItemData = $this->getStockItemData->execute($sku, $stockId);
                if (null === $stockItemData) {
                    return $this->productSalableResultFactory->create(['errors' => []]);
                }

                $backOrderQty = $requestedQty - $stockItemData[GetStockItemDataInterface::QUANTITY];
                if ($backOrderQty > 0) {
                    $errors = [
                        $this->productSalabilityErrorFactory->create([
                            'code' => 'back_order-not-enough',
                            'message' => __(
                                'We don\'t have as many quantity as you requested, '
                                . 'but we\'ll back order the remaining %1.',
                                $backOrderQty * 1
                            )
                        ])
                    ];
                    return $this->productSalableResultFactory->create(['errors' => $errors]);
                }
            }
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
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
}
