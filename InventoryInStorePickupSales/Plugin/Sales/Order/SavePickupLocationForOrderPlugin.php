<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Plugin\Sales\Order;

use Magento\InventoryInStorePickupSales\Model\Order\GetPickupLocationCode;
use Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderPickupLocation\SaveOrderPickupLocation;
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
     * @var GetPickupLocationCode
     */
    private $getPickupLocationCode;

    /**
     * @param SaveOrderPickupLocation $saveOrderPickupLocation
     * @param GetPickupLocationCode $getPickupLocationCode
     */
    public function __construct(
        SaveOrderPickupLocation $saveOrderPickupLocation,
        GetPickupLocationCode $getPickupLocationCode
    ) {
        $this->saveOrderPickupLocation = $saveOrderPickupLocation;
        $this->getPickupLocationCode = $getPickupLocationCode;
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
        $pickupLocationCode = $this->getPickupLocationCode->execute($result);

        if ($pickupLocationCode) {
            $this->saveOrderPickupLocation->execute((int)$result->getEntityId(), $pickupLocationCode);
        }

        return $result;
    }
}
