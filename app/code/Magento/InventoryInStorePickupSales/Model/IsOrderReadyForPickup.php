<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupSales\Model\Order\IsFulfillable;
use Magento\InventoryInStorePickupSalesApi\Model\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

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
     * @throws NoSuchEntityException
     */
    public function execute(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);

        return $this->isFulfillable->execute($order);
    }
}
