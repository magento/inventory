<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\Sales\Order;

use Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupLocation\GetPickupLocationByOrderId;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Set Pickup Location identifier to Order Entity.
 */
class GetPickupLocationForOrderPlugin
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var GetPickupLocationByOrderId
     */
    private $getPickupLocationByOrderId;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param GetPickupLocationByOrderId $getPickupLocationByOrderId
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        GetPickupLocationByOrderId $getPickupLocationByOrderId
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->getPickupLocationByOrderId = $getPickupLocationByOrderId;
    }

    /**
     * Add Pickup Location Code extension attribute when loading Order with OrderRepository.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface $order
     *
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(OrderRepositoryInterface $orderRepository, OrderInterface $order): OrderInterface
    {
        $extension = $order->getExtensionAttributes();

        if (empty($extension)) {
            $extension = $this->orderExtensionFactory->create();
        }

        if ($extension->getPickupLocationCode()) {
            return $order;
        }

        $pickupLocationCode = $this->getPickupLocationByOrderId->execute((int)$order->getEntityId());

        if ($pickupLocationCode) {
            $extension->setPickupLocationCode($pickupLocationCode);
        }

        $order->setExtensionAttributes($extension);

        return $order;
    }
}
