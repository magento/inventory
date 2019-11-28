<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Plugin\Sales\Model\ResourceModel\Order\Handler\State;

use Magento\InventoryInStorePickupSalesApi\Model\IsStorePickupOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;

/**
 * Process order status in case delivery method is 'pick in store'.
 */
class AdaptOrderStatusPlugin
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     */
    public function __construct(
        IsStorePickupOrderInterface $isStorePickupOrder
    ) {
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * Set order status as complete in case delivery method is 'store pickup' and shipment created.
     *
     * @param State $subject
     * @param \Closure $proceed
     * @param Order $order
     * @return State
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheck(
        State $subject,
        \Closure $proceed,
        Order $order
    ): State {
        if ($order->getEntityId()
            && $order->getState() === Order::STATE_PROCESSING
            && !$order->canShip()
            && $order->canInvoice()
            && $this->isStorePickupOrder->execute((int)$order->getEntityId())) {
            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));

            return $subject;
        }

        return $proceed($order);
    }
}
