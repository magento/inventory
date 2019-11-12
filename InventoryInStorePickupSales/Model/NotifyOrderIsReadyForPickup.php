<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickup\Model\Order\AddCommentToOrder;
use \Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupSalesApi\Model\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupSalesApi\Model\NotifyOrderIsReadyForPickupInterface;
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
     * @var Order\AddCommentToOrder
     */
    private $addCommentToOrder;

    /**
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
     * @param AddCommentToOrder $addCommentToOrder
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory,
        AddCommentToOrder $addCommentToOrder
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
        $this->shipmentArgumentsFactory = $shipmentArgumentsFactory;
        $this->argumentExtensionFactory = $argumentExtensionFactory;
        $this->addCommentToOrder = $addCommentToOrder;
    }

    /**
     * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY.
     *
     * Notify customer that the order is ready for pickup by sending notification email. Ship the order to deduct the
     * item quantity from the appropriate source.
     *
     * @inheritDoc
     */
    public function execute(int $orderId): void
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new LocalizedException(__('The order is not ready for pickup'));
        }

        $order = $this->orderRepository->get($orderId);

        /** @noinspection PhpParamsInspection */
        $this->emailNotifier->notify($order);
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
        $this->addCommentToOrder->execute($order);
    }

    /**
     * Get shipping arguments from the Order extension attributes.
     *
     * @param OrderInterface $order
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
