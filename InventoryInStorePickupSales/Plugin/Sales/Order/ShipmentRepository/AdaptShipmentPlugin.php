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
     * @param IsStorePickupOrderInterface $isOrderStorePickup
     */
    public function __construct(
        IsStorePickupOrderInterface $isOrderStorePickup
    ) {
        $this->isOrderStorePickup = $isOrderStorePickup;
    }

    /**
     * Restrict shipment creation in case order delivery method is "in store pickup".
     *
     * @see \Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface
     *
     * @param ShipmentRepositoryInterface $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeSave(ShipmentRepositoryInterface $subject, ShipmentInterface $shipment): void
    {
        if ($this->isOrderStorePickup->execute((int)$shipment->getOrderId())
            && !$shipment->getExtensionAttributes()->getIsNotified()
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
