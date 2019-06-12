<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\Sales\Order;

use Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupLocation\SaveOrderPickupLocation;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Save Pickup Location identifier, related to the Order Entity.
 */
class SavePickupLocationForOrderPlugin
{
    /**
     * @var SaveOrderPickupLocation
     */
    private $saveOrderPickupLocation;

    /**
     * @param SaveOrderPickupLocation $saveOrderPickupLocation
     */
    public function __construct(SaveOrderPickupLocation $saveOrderPickupLocation)
    {
        $this->saveOrderPickupLocation = $saveOrderPickupLocation;
    }

    /**
     * Save Order to Pickup Location relation when saving the order.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface $result
     * @param OrderInterface $entity
     *
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        OrderRepositoryInterface $orderRepository,
        OrderInterface $result,
        OrderInterface $entity
    ) {
        $extension = $result->getExtensionAttributes();

        if (null !== $extension && $extension->getPickupLocationCode()) {
            $this->saveOrderPickupLocation->execute((int)$result->getEntityId(), $extension->getPickupLocationCode());
        }

        return $result;
    }
}
