<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\ShippingAssignment;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingProcessor;

/**
 * Prevent a stale, previously selected non-pickup shipping method from being re-applied to a Pickup Location order.
 *
 * The shipping assignment (address + method) requested to be saved with the quote is snapshotted once, when the
 * quote is first loaded in the request, before any address change. If the address is switched to a Pickup Location
 * later in the same request, this stale snapshot still carries the earlier, now inconsistent, delivery method and
 * would otherwise be silently re-applied when the quote is saved.
 */
class PreventNonPickupShippingMethodReapplication
{
    /**
     * Clear the shipping method carried by the assignment when it no longer matches the Pickup Location address.
     *
     * @param ShippingProcessor $subject
     * @param ShippingInterface $shipping
     * @param CartInterface $quote
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(ShippingProcessor $subject, ShippingInterface $shipping, CartInterface $quote): array
    {
        $address = $shipping->getAddress();
        $pickupLocationCode = $address->getExtensionAttributes()?->getPickupLocationCode();
        $method = $shipping->getMethod();

        if ($pickupLocationCode && $method && $method !== InStorePickup::DELIVERY_METHOD) {
            $shipping->setMethod(null);
            $address->setShippingMethod(null);
        }

        return [$shipping, $quote];
    }
}
