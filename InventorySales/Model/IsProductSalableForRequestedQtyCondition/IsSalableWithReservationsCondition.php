<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition;

use Magento\CatalogInventory\Model\Config\Source\NotAvailableMessage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventorySalesApi\Model\GetSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;

/**
 * @inheritdoc
 */
class IsSalableWithReservationsCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param GetSalableQtyInterface $getProductQtyInStock
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly GetStockItemDataInterface $getStockItemData,
        private readonly ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        private readonly ProductSalableResultInterfaceFactory $productSalableResultFactory,
        private readonly GetSalableQtyInterface $getProductQtyInStock,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
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

        $qtyLeftInStock = $this->getProductQtyInStock->execute($sku, $stockId);
        $isEnoughQty = bccomp((string)$qtyLeftInStock, (string)$requestedQty, 4) >= 0;

        if (!$isEnoughQty) {
            $message = __('Not enough items for sale');
            if ((int)$this->scopeConfig->getValue(
                'cataloginventory/options/not_available_message'
            ) === NotAvailableMessage::VALUE_ONLY_X_OF_Y) {
                $message = (__(sprintf(
                    'Only %s of %s available',
                    $qtyLeftInStock,
                    $requestedQty
                )));
            }
            $errors = [
                $this->productSalabilityErrorFactory->create([
                    'code' => 'is_salable_with_reservations-not_enough_qty',
                    'message' => $message
                ])
            ];
            return $this->productSalableResultFactory->create(['errors' => $errors]);
        }
        return $this->productSalableResultFactory->create(['errors' => []]);
    }
}
