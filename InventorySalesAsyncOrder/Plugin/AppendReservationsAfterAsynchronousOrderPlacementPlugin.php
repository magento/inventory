<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Plugin;

use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventorySalesAsyncOrder\Model\Reservations;

class AppendReservationsAfterAsynchronousOrderPlacementPlugin
{
    /**
     * @var Reservations
     */
    private $inventoryReservations;

    /**
     * @var ReservationExecutionInterface
     */
    private $reservationExecution;

    /**
     * @param Reservations $inventoryReservations
     * @param ReservationExecutionInterface $reservationExecution
     */
    public function __construct(
        Reservations $inventoryReservations,
        ReservationExecutionInterface $reservationExecution
    ) {
        $this->inventoryReservations = $inventoryReservations;
        $this->reservationExecution = $reservationExecution;
    }

    /**
     * Add inventory reservation after async initial order is placed with no deferred stock update.
     *
     * @param OrderManagement $subject
     * @param OrderInterface $result
     * @return OrderInterface $result
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPlaceInitialOrder(
        OrderManagement $subject,
        OrderInterface $result
    ): OrderInterface {
        if (!$this->reservationExecution->isDeferred()) {
            $this->inventoryReservations->execute($result);
        }

        return $result;
    }
}
