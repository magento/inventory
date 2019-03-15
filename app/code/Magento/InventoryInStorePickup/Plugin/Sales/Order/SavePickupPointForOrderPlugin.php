<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\Sales\Order;

use Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupPoint\SaveOrderPickupPoint;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Save Pickup Point Identifier, related to the Order Entity.
 */
class SavePickupPointForOrderPlugin
{
    /**
     * @var SaveOrderPickupPoint
     */
    private $saveOrderPickupPoint;

    /**
     * SavePickupPointForOrderPlugin constructor.
     *
     * @param SaveOrderPickupPoint $saveOrderPickupPoint
     */
    public function __construct(SaveOrderPickupPoint $saveOrderPickupPoint)
    {
        $this->saveOrderPickupPoint = $saveOrderPickupPoint;
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

        if (!empty($extension) && $extension->getPickupPointId()) {
            $this->saveOrderPickupPoint->execute((int)$result->getEntityId(), $extension->getPickupPointId());
        }

        return $result;
    }
}
