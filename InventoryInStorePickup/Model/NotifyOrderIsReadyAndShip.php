<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyAndShipInterface;
use Magento\InventoryInStorePickupApi\Exception\OrderIsNotReadyForPickupException;

class NotifyOrderIsReadyAndShip implements NotifyOrderIsReadyAndShipInterface
{
    /**
     * @var \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface
     */
    private $isOrderReadyForPickup;

    /**
     * @var \Magento\Sales\Api\ShipOrderInterface
     */
    private $shipOrder;

    /**
     * NotifyOrderIsReadyAndShip constructor.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param \Magento\Sales\Api\ShipOrderInterface                                 $shipOrder
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        \Magento\Sales\Api\ShipOrderInterface $shipOrder
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(int $orderId):int
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new OrderIsNotReadyForPickupException();
        }

        /* TODO: send email */
        /* TODO: add order comment? */

        return $this->shipOrder->execute($orderId);
    }
}
