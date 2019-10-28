<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupMultishipping\Plugin\Quote;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

/**
 * Remove In-Store Pickup info if Quote in Multishipping mode.
 */
class RemoveInStorePickupDataInMultishippingModePlugin
{
    /**
     * Remove assigned shipping method and pickup location if quote in multi shipping mode.
     *
     * @param CartRepositoryInterface $repository
     * @param CartInterface $cart
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CartRepositoryInterface $repository, CartInterface $cart)
    {
        if (!$cart instanceof Quote || !$cart->getIsMultiShipping() || !$cart->getExtensionAttributes()) {
            return [$cart];
        }

        $extension = $cart->getExtensionAttributes();

        $assignments = $extension->getShippingAssignments();

        if (!$assignments) {
            return [$cart];
        }

        /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface $assignment */
        $assignment = current($assignments);
        $shipping = $assignment->getShipping();

        if ($shipping->getMethod() !== InStorePickup::DELIVERY_METHOD) {
            return [$cart];
        }

        $shipping->setMethod('');
        $address = $shipping->getAddress();
        if ($address instanceof Address) {
            $address->setShippingMethod('');
        }

        $addressExtension = $address->getExtensionAttributes();
        if ($addressExtension) {
            $addressExtension->setPickupLocationCode('');
        }

        return [$cart];
    }
}
