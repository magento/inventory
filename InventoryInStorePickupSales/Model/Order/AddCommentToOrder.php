<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupSales\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Add order comment regarding store pickup notification.
 */
class AddCommentToOrder
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
     * AddCommentToOrder constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        TimezoneInterface $timezone
    ) {
        $this->orderRepository = $orderRepository;
        $this->timezone = $timezone;
    }

    /**
     * Add notification comment to the order.
     *
     * @param OrderInterface $order
     * @throws \Exception
     * @return void
     */
    public function execute(OrderInterface $order) : void
    {
        $time = $this->timezone->formatDateTime(
            new \DateTime(),
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM
        );
        $comment = __('Order notified for pickup at: %1', $time);
        $order->addCommentToStatusHistory($comment, $order->getStatus(), true)->setIsCustomerNotified(1);
        $this->orderRepository->save($order);
    }
}
