<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickup\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;

/**
 * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY.
 */
class NotifyOrderIsReadyForPickup implements NotifyOrderIsReadyForPickupInterface
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
     * @var ReadyForPickupNotifier
     */
    private $emailNotifier;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ShipmentCreationArgumentsInterfaceFactory
     */
    private $shipmentArgumentsFactory;

    /**
     * @var ShipmentCreationArgumentsExtensionInterfaceFactory
     */
    private $argumentExtensionFactory;

    /**
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
        $this->shipmentArgumentsFactory = $shipmentArgumentsFactory;
        $this->argumentExtensionFactory = $argumentExtensionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $orderId): void
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new LocalizedException(__('The order is not ready for pickup'));
        }

        $order = $this->orderRepository->get($orderId);

        /** @noinspection PhpParamsInspection */
        $this->emailNotifier->notify($order);

        /* TODO: add order comment? */

        $this->shipOrder->execute(
            $orderId,
            [],
            false,
            false,
            null,
            [],
            [],
            $this->getShipmentArguments($order)
        );
    }

    /**
     * @param OrderInterface $order
     *
     * @return ShipmentCreationArgumentsInterface
     */
    private function getShipmentArguments(OrderInterface $order): ShipmentCreationArgumentsInterface
    {
        $arguments = $this->shipmentArgumentsFactory->create();
        /* We have already checked that PickupLocationCode exists */
        $extension = $this->argumentExtensionFactory
            ->create()
            ->setSourceCode(
                $order->getExtensionAttributes()->getPickupLocationCode()
            );
        $arguments->setExtensionAttributes($extension);

        return $arguments;
    }
}
