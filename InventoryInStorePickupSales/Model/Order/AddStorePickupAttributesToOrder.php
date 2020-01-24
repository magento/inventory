<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupSales\Model\Order;

use Magento\InventoryInStorePickupSalesApi\Model\IsStorePickupOrderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Modify order attributes according to store pickup information:
 * - Adds order history comment with In-Store Pickup notification information.
 * - Changes order status to "Complete" since shipping will not be provided.
 */
class AddStorePickupAttributesToOrder
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        IsStorePickupOrderInterface $isStorePickupOrder,
        OrderRepositoryInterface $orderRepository,
        TimezoneInterface $timezone
    ) {
        $this->orderRepository = $orderRepository;
        $this->timezone = $timezone;
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * Modify order attributes according to store pickup information.
     *
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    public function execute(OrderInterface $order): void
    {
        // Change order status to "Complete".
        if ($order->getEntityId()
            && $order->getState() === Order::STATE_PROCESSING
            && !$order->canShip()
            && $order->canInvoice()
            && $this->isStorePickupOrder->execute((int)$order->getEntityId())
        ) {
            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
        }

        // Add order history item with In-Store Pickup information.
        $time = $this->timezone->formatDateTime(new \DateTime(), \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM);
        $history = $order->addCommentToStatusHistory(
            __('Order notified for pickup at: %1', $time),
            $order->getStatus(),
            true
        );
        $history->setIsCustomerNotified((int)$order->getExtensionAttributes()->getNotificationSent());
        $this->orderRepository->save($order);
    }
}
