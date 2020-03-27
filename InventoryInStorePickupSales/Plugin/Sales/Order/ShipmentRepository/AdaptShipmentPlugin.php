<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Plugin\Sales\Order\ShipmentRepository;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupSalesApi\Model\IsStorePickupOrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Process shipment for store pickup delivery method.
 */
class AdaptShipmentPlugin
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isOrderStorePickup;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param IsStorePickupOrderInterface $isOrderStorePickup
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        IsStorePickupOrderInterface $isOrderStorePickup,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->isOrderStorePickup = $isOrderStorePickup;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Restrict shipment creation in case order delivery method is "in store pickup".
     *
     * @see \Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface
     *
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(ShipmentRepositoryInterface $subject, ShipmentInterface $shipment): void
    {
        $order = $this->orderRepository->get($shipment->getOrderId());
        if ($this->isOrderStorePickup->execute((int)$shipment->getOrderId())
            && null === $order->getExtensionAttributes()->getSendNotification()
        ) {
            throw new LocalizedException(
                __(
                    'Not able to create shipment with \'In Store Pick-Up\' delivery method.'
                    . ' Order should be notified as \'Ready For Pick-Up\' instead.'
                )
            );
        }
    }
}
