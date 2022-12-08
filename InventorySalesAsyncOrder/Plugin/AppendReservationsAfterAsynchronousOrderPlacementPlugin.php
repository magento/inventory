<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Plugin;

use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\InventorySalesAsyncOrder\Model\Reservations;

class AppendReservationsAfterAsynchronousOrderPlacementPlugin
{
    /**
     * @var Reservations
     */
    private Reservations $inventoryReservations;

    /**
     * @param Reservations $inventoryReservations
     */
    public function __construct(
        Reservations $inventoryReservations
    ) {
        $this->inventoryReservations = $inventoryReservations;
    }

    /**
     * Add inventory reservation after async initial order is placed
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
        $this->inventoryReservations->execute($result);

        return $result;
    }
}
