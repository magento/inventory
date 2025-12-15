<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;

/**
 * Get stock status changes for given reservation.
 */
class GetSalabilityDataForUpdate
{
    /**
     * @param GetParentSkusOfChildrenSkusInterface $getParentSkusOfChildrenSkus
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemsDataInterface $getStockItemsData
     */
    public function __construct(
        private readonly GetParentSkusOfChildrenSkusInterface $getParentSkusOfChildrenSkus,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly GetStockItemsDataInterface $getStockItemsData
    ) {
    }

    /**
     * Get stock status changes for given reservation.
     *
     * @param ReservationData $reservationData
     * @return array<string, bool> - ['sku' => bool]
     */
    public function execute(ReservationData $reservationData): array
    {
        $stockId = $reservationData->getStock();
        $skus = $reservationData->getSkus();
        $parentSkusOfChildrenSkus = $this->getParentSkusOfChildrenSkus->execute($skus);
        $skus = array_merge($skus, ...array_values($parentSkusOfChildrenSkus));
        $salabilityData = $this->areProductsSalable->execute($skus, $stockId);
        $currentStatuses = $this->getStockItemsData->execute($skus, $stockId);

        $data = [];
        foreach ($salabilityData as $isProductSalableResult) {
            $currentStatus = $currentStatuses[$isProductSalableResult->getSku()]['is_salable'];
            if ($isProductSalableResult->isSalable() !== $currentStatus) {
                $data[$isProductSalableResult->getSku()] = $isProductSalableResult->isSalable();
            }
        }

        return $data;
    }
}
