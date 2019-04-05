<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickup\Model\Order\CanBeFulfilled;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class IsOrderReadyForPickup implements IsOrderReadyForPickupInterface
{
    /**
     * @var \Magento\InventoryInStorePickup\Model\Order\CanBeFulfilled
     */
    private $canBeFulfilled;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param \Magento\InventoryInStorePickup\Model\Order\CanBeFulfilled $canBeFulfilled
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CanBeFulfilled $canBeFulfilled,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->canBeFulfilled = $canBeFulfilled;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function execute(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);

        return $this->canShip($order) && $this->canBeFulfilled->execute($order);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return bool
     */
    private function canShip(OrderInterface $order): bool
    {
        if ($order instanceof Order) {
            return $order->canShip();
        }

        return true;
    }
}
