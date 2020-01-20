<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterfaceFactory;

/**
 * Create shipping arguments from the Order extension attributes.
 */
class CreateShippingArguments
{
    /**
     * @var ShipmentCreationArgumentsInterfaceFactory
     */
    private $shipmentArgumentsFactory;

    /**
     * @var ShipmentCreationArgumentsExtensionInterfaceFactory
     */
    private $argumentExtensionFactory;

    /**
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
     */
    public function __construct(
        ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
    ) {
        $this->shipmentArgumentsFactory = $shipmentArgumentsFactory;
        $this->argumentExtensionFactory = $argumentExtensionFactory;
    }

    /**
     * Get shipping arguments from the Order extension attributes.
     *
     * @param OrderInterface $order
     * @return ShipmentCreationArgumentsInterface
     */
    public function execute(OrderInterface $order) : ShipmentCreationArgumentsInterface
    {
        $arguments = $this->shipmentArgumentsFactory->create();

        /* We have already checked that PickupLocationCode exists */
        $extension = $this->argumentExtensionFactory
            ->create()
            ->setSourceCode($order->getExtensionAttributes()->getPickupLocationCode());
        $arguments->setExtensionAttributes($extension);

        return $arguments;
    }
}
