<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyForPickupInterface;
use Magento\InventoryInStorePickupApi\Exception\OrderIsNotReadyForPickupException;

class NotifyOrderIsReadyForPickup implements NotifyOrderIsReadyForPickupInterface
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
     * @param \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param \Magento\Sales\Api\ShipOrderInterface $shipOrder
     * @param \Magento\InventoryInStorePickup\Model\Order\Email\ReadyForPickupNotifier $emailNotifier
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
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
    public function execute(int $orderId): void
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new OrderIsNotReadyForPickupException();
        }

        /** @noinspection PhpParamsInspection */
        $this->emailNotifier->notify($this->orderRepository->get($orderId));

        /* TODO: add order comment? */

        $this->shipOrder->execute($orderId);
    }
}
