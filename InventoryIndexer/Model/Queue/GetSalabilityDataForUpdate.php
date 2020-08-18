<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Get stock status changes for given reservation.
 */
class GetSalabilityDataForUpdate
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * Get stock status changes for given reservation.
     *
     * @param ReservationData $reservationData
     * @return bool[] - ['sku' => bool]
     */
    public function execute(ReservationData $reservationData): array
    {
        $salabilityData = $this->areProductsSalable->execute(
            $reservationData->getSkus(),
            $reservationData->getStock()
        );

        $data = [];
        foreach ($salabilityData as $isProductSalableResult) {
            $currentStatus = $this->isCurrentlySalable(
                $isProductSalableResult->getSku(),
                $reservationData->getStock()
            );
            if ($isProductSalableResult->isSalable() !== $currentStatus) {
                $data[$isProductSalableResult->getSku()] = $isProductSalableResult->isSalable();
            }
        }

        return $data;
    }

    /**
     * Get current is_salable value.
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     */
    private function isCurrentlySalable(string $sku, int $stockId): bool
    {
        try {
            $data = $this->getStockItemData->execute($sku, $stockId);
            $isSalable = $data ? (bool)$data[GetStockItemDataInterface::IS_SALABLE] : false;
        } catch (LocalizedException $e) {
            $isSalable = false;
        }

        return $isSalable;
    }
}
