<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\Sales\Order;

use Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupLocation\SaveOrderPickupLocation;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

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
     * SavePickupLocationForOrderPlugin constructor.
     *
     * @param SaveOrderPickupLocation $saveOrderPickupLocation
     */
    public function __construct(SaveOrderPickupLocation $saveOrderPickupLocation)
    {
        $this->saveOrderPickupLocation = $saveOrderPickupLocation;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\Data\OrderInterface      $result
     * @param \Magento\Sales\Api\Data\OrderInterface      $entity
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        OrderRepositoryInterface $orderRepository,
        OrderInterface $result,
        OrderInterface $entity
    ) {
        $extension = $result->getExtensionAttributes();

        if (!empty($extension) && $extension->getPickupLocationCode()) {
            $this->saveOrderPickupLocation->execute((int)$result->getEntityId(), $extension->getPickupLocationCode());
        }

        return $result;
    }
}
