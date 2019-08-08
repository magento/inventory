<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Address;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Get Address extension_attributes.pickup_location_code
 */
class GetAddressPickupLocationCode
{
    /**
     * Get Address extension_attributes.pickup_location_code
     *
     * @param AddressInterface $address
     * @return string|null
     */
    public function execute(AddressInterface $address): ?string
    {
        $extension = $address->getExtensionAttributes();
        if ($extension) {
            return $extension->getPickupLocationCode();
        }

        return null;
    }
}
