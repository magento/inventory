<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Extract Shipping Address fields from Pickup Location.
 */
class ExtractPickupLocationShippingAddressData
{
    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @param Copy $copyService
     */
    public function __construct(Copy $copyService)
    {
        $this->objectCopyService = $copyService;
    }

    /**
     * Extract Shipping Address fields from Pickup Location.
     *
     * @TODO Refactor when issue will be resolved in core.
     * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/23386.
     *
     * @param PickupLocationInterface $pickupLocation
     *
     * @return array
     */
    public function execute(PickupLocationInterface $pickupLocation): array
    {
        $pickupLocationAddressData = $this->objectCopyService->copyFieldsetToTarget(
            'inventory_convert_pickup_location',
            'to_pickup_location_shipping_address',
            $pickupLocation,
            []
        );

        return array_merge($pickupLocationAddressData, [
            AddressInterface::SAME_AS_BILLING => false,
            AddressInterface::SAVE_IN_ADDRESS_BOOK => false,
            AddressInterface::CUSTOMER_ADDRESS_ID => null,
            'shipping_method' => InStorePickup::DELIVERY_METHOD
        ]);
    }
}
