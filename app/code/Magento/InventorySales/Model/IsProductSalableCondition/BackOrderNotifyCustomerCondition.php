<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventorySales\Model\ResourceModel\GetSourceCodesBySkuAndStockId;
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
     * @var GetSourceCodesBySkuAndStockId
     */
    private $getSourceCodesBySkuAndStockId;

    /**
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
     */
    public function __construct(
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        GetStockItemDataInterface $getStockItemData,
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
    ) {
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getStockItemData = $getStockItemData;
        $this->getSourceCodesBySkuAndStockId = $getSourceCodesBySkuAndStockId;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $sourceCodes = $this->getSourceCodesBySkuAndStockId->execute($sku, $stockId);
        foreach ($sourceCodes as $sourceCode) {
            $backorders = $this->getBackorders($sku, $sourceCode, $globalSourceConfiguration);
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
     * @param string $sourceCode
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @return int
     */
    private function getBackorders(
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $globalConfiguration
    ): int {
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceCode);
        $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceCode);

        $defaultValue = $sourceConfiguration->getBackorders() !== null
            ? $sourceConfiguration->getBackorders()
            : $globalConfiguration->getBackorders();

        return $sourceItemConfiguration->getBackorders() !== null
            ? $sourceItemConfiguration->getBackorders()
            : $defaultValue;
    }
}
