<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\DistanceFilter;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;

/**
 * Create Source Selection Address from Pickup Locations Search Request Distance Filter.
 */
class ConvertToSourceSelectionAddress
{
    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;

    /**
     * @param AddressInterfaceFactory $addressInterfaceFactory
     */
    public function __construct(AddressInterfaceFactory $addressInterfaceFactory)
    {
        $this->addressInterfaceFactory = $addressInterfaceFactory;
    }

    /**
     * Create Source Selection Address based on Distance Fitler from Search Request.
     *
     * @param DistanceFilterInterface $distanceFilter
     * @return AddressInterface
     */
    public function execute(DistanceFilterInterface $distanceFilter): AddressInterface
    {
        $data = [
            'country' => $distanceFilter->getCountry(),
            'postcode' => $distanceFilter->getPostcode() ?? '',
            'region' => $distanceFilter->getRegion() ?? '',
            'city' => $distanceFilter->getCity() ?? '',
            'street' => ''
        ];

        return $this->addressInterfaceFactory->create($data);
    }

}
