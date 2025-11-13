<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Finalize shipping address data for the Store Pickup cart.
 */
class GetShippingAddressData
{
    /**
     * Finalize shipping address data for the Store Pickup cart.
     *
     * @return array
     */
    public function execute(): array
    {
        return [
            AddressInterface::SAME_AS_BILLING => false,
            AddressInterface::SAVE_IN_ADDRESS_BOOK => false,
            AddressInterface::CUSTOMER_ADDRESS_ID => null,
            'shipping_method' => InStorePickup::DELIVERY_METHOD
        ];
    }
}
