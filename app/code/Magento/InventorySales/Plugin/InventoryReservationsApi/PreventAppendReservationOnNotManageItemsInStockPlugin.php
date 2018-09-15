<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\InventoryReservationsApi;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Prevent append reservation if use_config_manage_stock is set to 0
 */
class PreventAppendReservationOnNotManageItemsInStockPlugin
{
    /**
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        GetInventoryConfigurationInterface $getInventoryConfiguration,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->getInventoryConfiguration = $getInventoryConfiguration;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @param AppendReservationsInterface $subject
     * @param \Closure $proceed
     * @param ReservationInterface[] $reservations
     *
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AppendReservationsInterface $subject, \Closure $proceed, array $reservations)
    {
        if (!$this->stockConfiguration->canSubtractQty()) {
            return;
        }

        $reservationToAppend = [];
        foreach ($reservations as $reservation) {
            $isManageStock = $this->getInventoryConfiguration->isManageStock(
                $reservation->getSku(),
                $reservation->getStockId()
            );

            if ($isManageStock) {
                $reservationToAppend[] = $reservation;
            }
        }

        if (!empty($reservationToAppend)) {
            $proceed($reservationToAppend);
        }
    }
}
