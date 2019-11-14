<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickup\Model\Order\IsFulfillable;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Check if order can be shipped and the pickup location has enough QTY
 */
class IsOrderReadyForPickup implements IsOrderReadyForPickupInterface
{
    /**
     * @var IsFulfillable
     */
    private $isFulfillable;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param IsFulfillable $isFulfillable
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        IsFulfillable $isFulfillable,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->isFulfillable = $isFulfillable;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Check if order can be shipped and the pickup location has enough QTY.
     *
     * @param int $orderId
     * @return bool
     */
    public function execute(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);

        return $this->canShip($order) && $this->isFulfillable->execute($order);
    }

    /**
     * Retrieve order shipment availability.
     *
     * @param OrderInterface $order
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
