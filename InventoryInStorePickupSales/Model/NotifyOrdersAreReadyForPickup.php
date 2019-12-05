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
use Magento\InventoryInStorePickupSales\Model\Order\IsFulfillable;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterfaceFactory;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;

/**
 * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY..
 */
class NotifyOrdersAreReadyForPickup implements NotifyOrdersAreReadyForPickupInterface
{
    /**
     * @var IsFulfillable
     */
    private $isFulfillable;

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
     * @param IsFulfillable $isFulfillable
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param AddCommentToOrder $addCommentToOrder
     * @param ResultInterfaceFactory $resultFactory
     * @param CreateShippingArguments $createShippingArguments
     */
    public function __construct(
        IsFulfillable $isFulfillable,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        AddCommentToOrder $addCommentToOrder,
        ResultInterfaceFactory $resultFactory,
        CreateShippingArguments $createShippingArguments
    ) {
        $this->isFulfillable = $isFulfillable;
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
            try {
                $order = $this->orderRepository->get($orderId);
                if (!$this->isFulfillable->execute($order) && $order->canShip()) {
                    $failed[] = [
                        'id' => $orderId,
                        'message' => 'The order is not ready for pickup',
                    ];
                    continue;
                }
                $this->emailNotifier->notify($order);
                if ($order->canShip()) {
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
                }
                $this->addCommentToOrder->execute($order);
            } catch (LocalizedException $exception) {
                $failed[] = [
                    'id' => $orderId,
                    'message' => $exception->getMessage(),
                ];
                continue;
            } catch (\Exception $exception) {
                $failed[] = [
                    'id' => $orderId,
                    'message' => 'We can\'t notify the customer right now.',
                ];
                continue;
            }
        }

        return $this->resultFactory->create(['failed' => $failed]);
    }
}
