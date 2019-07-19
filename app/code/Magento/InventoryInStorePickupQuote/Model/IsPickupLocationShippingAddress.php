<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * Check if provided Shipping Address is address of Pickup Location.
 */
class IsPickupLocationShippingAddress
{
    /**
     * @var ExtractPickupLocationShippingAddressData
     */
    private $extractPickupLocationShippingAddressData;

    /**
     * @param ExtractPickupLocationShippingAddressData $extractPickupLocationShippingAddressData
     */
    public function __construct(ExtractPickupLocationShippingAddressData $extractPickupLocationShippingAddressData)
    {
        $this->extractPickupLocationShippingAddressData = $extractPickupLocationShippingAddressData;
    }

    /**
     * Check if Address is Pickup Location address.
     *
     * @param PickupLocationInterface $pickupLocation
     * @param Address $shippingAddress
     *
     * @return bool
     */
    public function execute(PickupLocationInterface $pickupLocation, Address $shippingAddress): bool
    {
        $data = $this->extractPickupLocationShippingAddressData->execute($pickupLocation);

        if (!$shippingAddress->getExtensionAttributes() ||
            !$shippingAddress->getExtensionAttributes()->getPickupLocationCode()
        ) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ($shippingAddress->getData($key) != $value) {
                return false;
            }
        }

        return true;
    }
}
