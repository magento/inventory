<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Observer\AsyncOrder\OrderManagement;

use Magento\Framework\Event\ObserverInterface;
use Magento\InventorySales\Model\AppendReservations;

class AppendReservationsAfterAsyncOrderPlacementObserver implements ObserverInterface
{

    /**
     * @param AppendReservations
     */
    private AppendReservations $appendReservations;

    /**
     * @param AppendReservations $appendReservations
     */
    public function __construct(
        AppendReservations $appendReservations
    ) {
        $this->appendReservations = $appendReservations;
    }

    /**
     * Add reservation after placing Async order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getAsyncOrder();
        $this->appendReservations->execute($order);
    }
}
