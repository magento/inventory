<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrder;

/**
 * Set Pickup Location code to the Order from Quote Address.
 *
 * The Pickup Location code will be pass to the Order only if selected delivery method is In-Store Pickup.
 */
class SetPickupLocationToOrder
{
    /**
     * Add Pickup Location code to the Order from Quote Address.
     *
     * @param ToOrder $subject
     * @param Address $address
     * @param array $data
     *
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
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][PickupLocationInterface::PICKUP_LOCATION_CODE] =
                $extension->getPickupLocationCode();
        }

        return [$address, $data];
    }
}
