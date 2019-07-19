<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Convert;

use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface as SourceSelectionAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;

/**
 * Create Source Selection Address based on Pickup Locations Search Criteria.
 */
class ToSourceSelectionAddress
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
     * Create Source Selection Address based on Pickup Locations Search Criteria.
     *
     * @param GetNearbyLocationsCriteriaInterface $searchCriteria
     * @return SourceSelectionAddressInterface
     */
    public function execute(GetNearbyLocationsCriteriaInterface $searchCriteria): SourceSelectionAddressInterface
    {
        $data = [
            'country' => $searchCriteria->getCountry(),
            'postcode' => $searchCriteria->getPostcode() ?? '',
            'region' => $searchCriteria->getRegion() ?? '',
            'city' => $searchCriteria->getCity() ?? '',
            'street' => ''
        ];

        return $this->addressInterfaceFactory->create($data);
    }
}
