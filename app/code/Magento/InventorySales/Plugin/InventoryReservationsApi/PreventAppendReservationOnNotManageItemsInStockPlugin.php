<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\InventoryReservationsApi;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Prevent append reservation if use_config_manage_stock is set to 0
 */
class PreventAppendReservationOnNotManageItemsInStockPlugin
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        GetStockConfigurationInterface $getStockItemConfiguration,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->getStockConfiguration = $getStockItemConfiguration;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @param AppendReservationsInterface $subject
     * @param \Closure $proceed
     * @param ReservationInterface[] $reservations
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AppendReservationsInterface $subject, \Closure $proceed, array $reservations)
    {
        if (!$this->stockConfiguration->canSubtractQty()) {
            return;
        }

        $reservationToAppend = [];
        foreach ($reservations as $reservation) {
            $isManageStock = $this->isManageStock($reservation);

            if ($isManageStock) {
                $reservationToAppend[] = $reservation;
            }
        }

        if (!empty($reservationToAppend)) {
            $proceed($reservationToAppend);
        }
    }

    /**
     * @param ReservationInterface $reservation
     * @return bool
     */
    private function isManageStock(ReservationInterface $reservation): bool
    {
        $stockConfiguration = $this->getStockConfiguration->forStock($reservation->getStockId());
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $reservation->getSku(),
            $reservation->getStockId()
        );
        $defaultValue = $stockConfiguration->isManageStock() !== null
            ? $stockConfiguration->isManageStock()
            : $globalConfiguration->isManageStock();
        $isManageStock = $stockItemConfiguration->isManageStock() !== null
            ? $stockItemConfiguration->isManageStock()
            : $defaultValue;

        return $isManageStock;
    }
}
