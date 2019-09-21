<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Finalize shipping address data for the Store Pickup cart.
 */
class BuildShippingAddressData
{
    /**
     * Finalize shipping address data for the Store Pickup cart.
     *
     * @param array $addressData
     *
     * @return array
     */
    public function execute(array $addressData): array
    {
        return array_merge(
            $addressData,
            [
                AddressInterface::SAME_AS_BILLING => false,
                AddressInterface::SAVE_IN_ADDRESS_BOOK => false,
                AddressInterface::CUSTOMER_ADDRESS_ID => null,
                'shipping_method' => InStorePickup::DELIVERY_METHOD
            ]
        );
    }
}
