<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

namespace Magento\InventoryInStorePickupSales\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupSalesApi\Model\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\ShipOrderInterface;

/**
 * Create shipping document for provided order.
 */
class CreateShippingDocument
{
    /**
     * @var IsOrderReadyForPickupInterface
     */
    private $isOrderReadyForPickup;
    /**
     * @var ShipOrderInterface
     */
    private $shipOrder;
    /**
     * @var CreateShippingArguments
     */
    private $createShippingArguments;

    /**
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param CreateShippingArguments $createShippingArguments
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        CreateShippingArguments $createShippingArguments
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->createShippingArguments = $createShippingArguments;
    }

    /**
     * Create order shipping document
     *
     * @param OrderInterface $order
     * @throws LocalizedException
     * @return void
     */
    public function execute(OrderInterface $order) : void
    {
        if (!$this->isOrderReadyForPickup->execute((int)$order->getEntityId())) {
            throw new LocalizedException(__('The order is not ready for pickup'));
        }

        $this->shipOrder->execute(
            (int)$order->getEntityId(),
            [],
            false,
            false,
            null,
            [],
            [],
            $this->createShippingArguments->execute($order)
        );
    }
}
