<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickup\Model\Order\IsFulfilled;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class IsOrderReadyForPickup implements IsOrderReadyForPickupInterface
{
    /**
     * @var \Magento\InventoryInStorePickup\Model\Order\IsFulfilled
     */
    private $isFulfilled;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * IsReadyForPickup constructor.
     *
     * @param \Magento\InventoryInStorePickup\Model\Order\IsFulfilled $isFulfilled
     * @param \Magento\Sales\Api\OrderRepositoryInterface             $orderRepository
     */
    public function __construct(
        IsFulfilled $isFulfilled,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->isFulfilled = $isFulfilled;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function execute(int $orderId):bool
    {
        return $this->canShip($orderId) && $this->isFulfilled->execute($orderId);
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    private function canShip(int $orderId):bool
    {
        $order = $this->orderRepository->get($orderId);
        if ($order instanceof \Magento\Sales\Model\Order) {
            return $order->canShip();
        }

        return true;
    }
}
