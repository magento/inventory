<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyAndShipInterface;
use Magento\InventoryInStorePickupApi\Exception\OrderIsNotReadyForPickupException;

class NotifyOrderIsReadyAndShip implements NotifyOrderIsReadyAndShipInterface
{
    /**
     * @var \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface
     */
    private $isOrderReadyForPickup;

    /**
     * @var \Magento\Sales\Api\ShipOrderInterface
     */
    private $shipOrder;

    /**
     * @var \Magento\InventoryInStorePickup\Model\Order\Email\ReadyForPickupNotifier
     */
    private $emailNotifier;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * NotifyOrderIsReadyAndShip constructor.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface    $isOrderReadyForPickup
     * @param \Magento\Sales\Api\ShipOrderInterface                                    $shipOrder
     * @param \Magento\InventoryInStorePickup\Model\Order\Email\ReadyForPickupNotifier $emailNotifier
     * @param \Magento\Sales\Api\OrderRepositoryInterface                              $orderRepository
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        \Magento\Sales\Api\ShipOrderInterface $shipOrder,
        Order\Email\ReadyForPickupNotifier $emailNotifier,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(int $orderId):?int
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new OrderIsNotReadyForPickupException();
        }

        $this->emailNotifier->notify($this->getOrder($orderId));
        /* TODO: add order comment? */

        return (int)$this->shipOrder->execute($orderId);
    }

    /**
     * @param int $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order
     */
    private function getOrder(int $orderId)
    {
        return $this->orderRepository->get($orderId);
    }
}
