<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupSales\Model\Order\AddCommentToOrder;
use Magento\InventoryInStorePickupSales\Model\Order\CreateShippingArguments;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupSalesApi\Model\IsOrderReadyForPickupInterface;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\InventoryInStorePickupSalesApi\Model\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Model\ResultInterfaceFactory;

/**
 * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY..
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
     * @var Order\AddCommentToOrder
     */
    private $addCommentToOrder;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var CreateShippingArguments
     */
    private $createShippingArguments;

    /**
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param AddCommentToOrder $addCommentToOrder
     * @param ResultInterfaceFactory $resultFactory
     * @param CreateShippingArguments $createShippingArguments
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        AddCommentToOrder $addCommentToOrder,
        ResultInterfaceFactory $resultFactory,
        CreateShippingArguments $createShippingArguments
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
        $this->addCommentToOrder = $addCommentToOrder;
        $this->resultFactory = $resultFactory;
        $this->createShippingArguments = $createShippingArguments;
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
                $this->emailNotifier->notify($order);
                $this->shipOrder->execute(
                    $orderId,
                    [],
                    false,
                    false,
                    null,
                    [],
                    [],
                    $this->createShippingArguments->execute($order)
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
}
