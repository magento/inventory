<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupSales\Model\Order\AddCommentToOrder;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupSalesApi\Model\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupSalesApi\Model\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\InventoryInStorePickupSalesApi\Model\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Model\ResultInterfaceFactory;

/**
 * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY.
 */
class NotifyOrdersAreReadyForPickup implements NotifyOrdersAreReadyForPickupInterface
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
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory
     * @param ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory
     * @param AddCommentToOrder $addCommentToOrder
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        ShipmentCreationArgumentsInterfaceFactory $shipmentArgumentsFactory,
        ShipmentCreationArgumentsExtensionInterfaceFactory $argumentExtensionFactory,
        AddCommentToOrder $addCommentToOrder,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
        $this->shipmentArgumentsFactory = $shipmentArgumentsFactory;
        $this->argumentExtensionFactory = $argumentExtensionFactory;
        $this->addCommentToOrder = $addCommentToOrder;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY.
     *
     * Notify customer that the order is ready for pickup by sending notification email. Ship the order to deduct the
     * item quantity from the appropriate source.
     *
     * @inheritdoc
     */
    public function execute(array $orderIds): ResultInterface
    {
        $failed = [];
        foreach ($orderIds as $orderId) {
            if (!$this->isOrderReadyForPickup->execute($orderId)) {
                $failed[] = [
                    'id' => $orderId,
                    'message' => 'The order is not ready for pickup'
                ];
                continue;
            }

            try {
                $order = $this->orderRepository->get($orderId);

                if (!$this->emailNotifier->notify($order)) {
                    $failed[] = [
                        'id' => $orderId,
                        'message' => 'Cannot notify user.'
                    ];
                    continue;
                }

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
            } catch (LocalizedException $exception) {
                $failed[] = [
                    'id' => $orderId,
                    'message' => $exception->getMessage()
                ];
                continue;
            } catch (\Exception $exception) {
                $failed[] = [
                    'id' => $orderId,
                    'message' => 'We can\'t notify the customer right now.'
                ];
                continue;
            }
        }

        return $this->resultFactory->create(['failed' => $failed]);
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
