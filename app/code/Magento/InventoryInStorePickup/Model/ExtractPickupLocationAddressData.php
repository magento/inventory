<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Extract Address fields from Pickup Location.
 */
class ExtractPickupLocationAddressData
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
     * Extract Address fields from Pickup Location.
     *
     * @param PickupLocationInterface $pickupLocation
     *
     * @return array
     */
    public function execute(PickupLocationInterface $pickupLocation): array
    {
        return $this->objectCopyService->getDataFromFieldset(
            'inventory_convert_pickup_location',
            'to_in_store_pickup_shipping_address',
            $pickupLocation
        );
    }
}
