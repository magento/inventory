<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\Copy;
use Magento\InventoryApi\Api\Data\SourceInterface;
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
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var array
     */
    private $regions = [];

    /**
     * @param Copy $copyService
     * @param RegionFactory|null $regionFactory
     */
    public function __construct(
        Copy $copyService,
        ?RegionFactory $regionFactory = null
    ) {
        $this->objectCopyService = $copyService;
        $this->regionFactory = $regionFactory ?:
            ObjectManager::getInstance()->get(RegionFactory::class);
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
        $data = $this->retrieveRegion($pickupLocation, $data);

        return $data;
    }

    /**
     * Retrieve region name by current locale
     *
     * @param PickupLocationInterface $pickupLocation
     * @param array $data
     * @return array
     */
    private function retrieveRegion(PickupLocationInterface $pickupLocation, array $data): array
    {
        $cacheKey = $pickupLocation->getCountryId() . '_' . $pickupLocation->getRegionId();

        if (!isset($this->regions[$cacheKey])) {
            $region = $this->regionFactory->create();
            $region->loadByName($pickupLocation->getRegion(), $pickupLocation->getCountryId());
            $this->regions[$cacheKey] = $region->getName() ?: $pickupLocation->getRegion();
        }

        $data[SourceInterface::REGION] = $this->regions[$cacheKey];

        return $data;
    }
}
