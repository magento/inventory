<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Plugin\Sales\Model\AdminOrder\Create;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Model\AdminOrder\Create;

/**
 * Update quote shipping address plugin.
 */
class AdaptSetShippingAddressPlugin
{
    /**
     * Update quote shipping address in case delivery method is 'in store pickup'.
     *
     * @param Create $subject
     * @param \Closure $proceed
     * @param array $address
     * @return Create
     */
    public function aroundSetShippingAddress(Create $subject, \Closure $proceed, $address): Create
    {
        $quoteAddress = $subject->getShippingAddress();
        if ($quoteAddress->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
            $subject->setShippingMethod(InStorePickup::DELIVERY_METHOD);
            $address = array_merge($quoteAddress->getData(), $address);
            $quoteAddress->setData($address);
            $subject->getQuote()->setShippingAddress($quoteAddress);

            return $subject;
        }

        return $proceed($address);
    }
}
