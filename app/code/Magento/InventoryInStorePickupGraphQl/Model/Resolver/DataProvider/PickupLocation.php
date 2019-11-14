<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\DataProvider;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Pickup Location data provider.
 */
class PickupLocation
{
    /**
     * Get Pickup Location data.
     *
     * @param PickupLocationInterface $pickupLocation
     *
     * @return array
     */
    public function getData(PickupLocationInterface $pickupLocation): array
    {
        return [
            PickupLocationInterface::PICKUP_LOCATION_CODE => $pickupLocation->getPickupLocationCode(),
            SourceInterface::NAME => $pickupLocation->getName(),
            SourceInterface::DESCRIPTION => $pickupLocation->getDescription(),
            SourceInterface::EMAIL => $pickupLocation->getEmail(),
            SourceInterface::FAX => $pickupLocation->getFax(),
            SourceInterface::CONTACT_NAME => $pickupLocation->getContactName(),
            SourceInterface::LATITUDE => $pickupLocation->getLatitude(),
            SourceInterface::LONGITUDE => $pickupLocation->getLongitude(),
            SourceInterface::COUNTRY_ID => $pickupLocation->getCountryId(),
            SourceInterface::REGION_ID => $pickupLocation->getRegionId(),
            SourceInterface::REGION => $pickupLocation->getRegion(),
            SourceInterface::CITY => $pickupLocation->getCity(),
            SourceInterface::STREET => $pickupLocation->getStreet(),
            SourceInterface::POSTCODE => $pickupLocation->getPostcode(),
            SourceInterface::PHONE => $pickupLocation->getPhone()
        ];
    }
}
