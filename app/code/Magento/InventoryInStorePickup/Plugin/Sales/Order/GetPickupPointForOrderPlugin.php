<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\Sales\Order;

use Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupPoint\GetPickupPointByOrderId;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Set Pickup Point Identifier to Order Entity.
 */
class GetPickupPointForOrderPlugin
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var GetPickupPointByOrderId
     */
    private $getPickupPointByOrderId;

    /**
     * GetPickupPointForOrderPlugin constructor.
     *
     * @param OrderExtensionFactory   $orderExtensionFactory
     * @param GetPickupPointByOrderId $getPickupPointByOrderId
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        GetPickupPointByOrderId $getPickupPointByOrderId
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->getPickupPointByOrderId = $getPickupPointByOrderId;
    }

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface           $order
     *
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        OrderRepositoryInterface $orderRepository,
        OrderInterface $order
    ):OrderInterface {
        $extension = $order->getExtensionAttributes();

        if (empty($extension)) {
            $extension = $this->orderExtensionFactory->create();
        }

        if ($extension->getPickupPointId()) {
            return $order;
        }

        $pickupPointId = $this->getPickupPointByOrderId->execute((int)$order->getEntityId());

        if ($pickupPointId) {
            $extension->setPickupPointId($pickupPointId);
        }

        $order->setExtensionAttributes($extension);

        return $order;
    }
}
