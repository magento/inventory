<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition;

use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * @inheritdoc
 */
class IsSalableWithReservationsCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetStockConfigurationInterface $getStockItemConfiguration,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->getStockConfiguration = $getStockItemConfiguration;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            $errors = [
                $this->productSalabilityErrorFactory->create([
                    'code' => 'is_salable_with_reservations-no_data',
                    'message' => __('The requested sku is not assigned to given stock')
                ])
            ];
            return $this->productSalableResultFactory->create(['errors' => $errors]);
        }

        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $minQty = $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);

        $qtyWithReservation = $stockItemData[GetStockItemDataInterface::QUANTITY] +
            $this->getReservationsQuantity->execute($sku, $stockId);
        $qtyLeftInStock = $qtyWithReservation - $minQty - $requestedQty;
        $isEnoughQty = (bool)$stockItemData[GetStockItemDataInterface::IS_SALABLE] && $qtyLeftInStock >= 0;
        if (!$isEnoughQty) {
            $errors = [
                $this->productSalabilityErrorFactory->create([
                    'code' => 'is_salable_with_reservations-not_enough_qty',
                    'message' => __('The requested qty is not available')
                ])
            ];
            return $this->productSalableResultFactory->create(['errors' => $errors]);
        }
        return $this->productSalableResultFactory->create(['errors' => []]);
    }

    /**
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return float
     */
    private function getMinQty(
        StockItemConfigurationInterface $globalConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ): float {
        $defaultValue = $stockConfiguration->getMinQty() !== null
            ? $stockConfiguration->getMinQty()
            : $globalConfiguration->getMinQty();

        return $stockItemConfiguration->getMinQty() !== null ? $stockItemConfiguration->getMinQty(): $defaultValue;
    }
}
