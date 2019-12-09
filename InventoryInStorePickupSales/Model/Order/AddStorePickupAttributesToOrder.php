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
 * Modify order attributes according to store pickup information.
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
     * AddCommentToOrder constructor.
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
     * Add notification comment to the order.
     *
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    public function execute(OrderInterface $order) : void
    {
        $this->setOrderStatus($order);
        $this->addOrderComment($order);
        $this->orderRepository->save($order);
    }

    /**
     * Add order comment with store pickup information.
     *
     * @param OrderInterface $order
     * @throws \Exception
     * @return void
     */
    private function addOrderComment(OrderInterface $order): void
    {
        $time = $this->timezone->formatDateTime(new \DateTime(), \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM);
        $order->addCommentToStatusHistory(__('Order notified for pickup at: %1', $time), $order->getStatus(), true);
        $order->setIsCustomerNotified($order->getEmailSent());
    }

    /**
     * Change order status to Complete.
     *
     * @param OrderInterface $order
     * @return void
     */
    private function setOrderStatus(OrderInterface $order) : void
    {
        if ($order->getEntityId()
            && $order->getState() === Order::STATE_PROCESSING
            && !$order->canShip()
            && $order->canInvoice()
            && $this->isStorePickupOrder->execute((int)$order->getEntityId())
        ) {
            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
        }
    }
}
