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
     * @param IsOrderReadyForPickupInterface $isOrderReadyForPickup
     * @param ShipOrderInterface $shipOrder
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        IsOrderReadyForPickupInterface $isOrderReadyForPickup,
        ShipOrderInterface $shipOrder,
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->isOrderReadyForPickup = $isOrderReadyForPickup;
        $this->shipOrder = $shipOrder;
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $orderId): void
    {
        if (!$this->isOrderReadyForPickup->execute($orderId)) {
            throw new LocalizedException(__('The order is not ready for pickup'));
        }

        /** @noinspection PhpParamsInspection */
        $this->emailNotifier->notify($this->orderRepository->get($orderId));

        /* TODO: add order comment? */

        $this->shipOrder->execute($orderId);
    }
}
