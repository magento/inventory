<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickup\Model\PickupLocation\DataResolver as PickupLocationDataResolver;
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
     * @var PickupLocationDataResolver
     */
    private $pickupLocationDataResolver;

    /**
     * @param Copy $copyService
     * @param PickupLocationDataResolver|null $pickupLocationDataResolver
     */
    public function __construct(
        Copy $copyService,
        ?PickupLocationDataResolver $pickupLocationDataResolver = null
    ) {
        $this->objectCopyService = $copyService;
        $this->pickupLocationDataResolver = $pickupLocationDataResolver ?:
            ObjectManager::getInstance()->get(PickupLocationDataResolver::class);
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
        $data = $this->objectCopyService->getDataFromFieldset(
            'inventory_convert_pickup_location',
            'to_in_store_pickup_shipping_address',
            $pickupLocation
        );
        $data = $this->pickupLocationDataResolver->execute($pickupLocation, $data);

        return $data;
    }
}
