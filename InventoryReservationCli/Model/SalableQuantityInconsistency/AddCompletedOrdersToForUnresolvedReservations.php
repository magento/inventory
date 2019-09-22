<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\InventoryReservationCli\Model\GetOrdersInFinalState;

/**
 * Match completed orders with unresolved reservations
 */
class AddCompletedOrdersToForUnresolvedReservations
{
    /**
     * @var GetOrdersInFinalState
     */
    private $getOrdersInFinalState;

    /**
     * @param GetOrdersInFinalState $getOrdersInFinalState
     */
    public function __construct(
        GetOrdersInFinalState $getOrdersInFinalState
    ) {
        $this->getOrdersInFinalState = $getOrdersInFinalState;
    }

    /**
     * Remove all entries without order
     *
     * @param Collector $collector
     * @param int $bunchSize
     * @param int $page
     */
    public function execute(Collector $collector, int $bunchSize = 50, int $page = 1): void
    {
        $inconsistencies = $collector->getItems();

        $orderIds = [];
        foreach ($inconsistencies as $inconsistency) {
            $orderIds[] = $inconsistency->getObjectId();
        }

        foreach ($this->getOrdersInFinalState->execute($orderIds, $bunchSize, $page) as $order) {
            $collector->addOrder($order);
        }

        $collector->setItems($inconsistencies);
    }
}
