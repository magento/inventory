<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickup\Model\Order\GetPickupLocationCode;
use Magento\InventoryInStorePickupApi\Api\IsStorePickupOrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @inheritDoc
 */
class IsStorePickupOrder implements IsStorePickupOrderInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetPickupLocationCode
     */
    private $getPickupLocationCode;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param GetPickupLocationCode $getPickupLocationCode
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        GetPickupLocationCode $getPickupLocationCode
    ) {
        $this->orderRepository = $orderRepository;
        $this->getPickupLocationCode = $getPickupLocationCode;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);

        return (bool)$this->getPickupLocationCode->execute($order);
    }
}
