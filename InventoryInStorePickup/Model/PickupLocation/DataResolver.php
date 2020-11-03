<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation;

use Magento\Directory\Model\RegionFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Get data based on the current locale
 */
class DataResolver
{
    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @param RegionFactory $regionFactory
     */
    public function __construct(RegionFactory $regionFactory)
    {
        $this->regionFactory = $regionFactory;
    }

    /**
     * Retrieve data by current locale
     *
     * @param PickupLocationInterface $pickupLocation
     * @param array $data
     *
     * @return array
     */
    public function execute(PickupLocationInterface $pickupLocation, array $data): array
    {
        return $this->retrieveRegion($pickupLocation, $data);
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
        $region = $this->regionFactory->create();
        $region->loadByName($pickupLocation->getRegion(), $pickupLocation->getCountryId());
        if ($region->getName()) {
            $data[SourceInterface::REGION] = $region->getName();
        }

        return $data;
    }
}
