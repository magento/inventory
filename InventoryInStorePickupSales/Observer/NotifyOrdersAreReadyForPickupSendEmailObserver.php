<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryInStorePickupSales\Model\NotifyOrdersAreReadyForPickupEmailSender;

/**
 * Send emails for orders with 'ready for pickup' in case asynchronous email sending has been disabled observer.
 */
class NotifyOrdersAreReadyForPickupSendEmailObserver implements ObserverInterface
{
    /**
     * @var NotifyOrdersAreReadyForPickupEmailSender
     */
    private $ordersAreReadyForPickupEmailSender;

    /**
     * @param NotifyOrdersAreReadyForPickupEmailSender $ordersAreReadyForPickupEmailSender
     */
    public function __construct(NotifyOrdersAreReadyForPickupEmailSender $ordersAreReadyForPickupEmailSender)
    {
        $this->ordersAreReadyForPickupEmailSender = $ordersAreReadyForPickupEmailSender;
    }

    /**
     * Send emails for orders with 'ready for pickup' in case asynchronous email sending has been disabled.
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $this->ordersAreReadyForPickupEmailSender->execute();
    }
}
