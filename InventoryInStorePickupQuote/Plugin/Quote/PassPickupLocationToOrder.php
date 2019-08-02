<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrder;

/**
 * Pass Pickup Location code to the Order from Quote Address.
 */
class PassPickupLocationToOrder
{
    private const ORDER_FIELD_NAME = 'extension_attribute_pickup_location_code_pickup_location_code';

    /**
     * Add Pickup Location code to the Order from Quote Address.
     *
     * @param ToOrder $subject
     * @param Address $address
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeConvert(ToOrder $subject, Address $address, array $data = []): array
    {
        if ($address->getShippingMethod() !== InStorePickup::DELIVERY_METHOD) {
            return [$address, $data];
        }

        $extension = $address->getExtensionAttributes();

        if ($extension && $extension->getPickupLocationCode()) {
            $data[self::ORDER_FIELD_NAME] = $extension->getPickupLocationCode();
        }

        return [$address, $data];
    }
}
