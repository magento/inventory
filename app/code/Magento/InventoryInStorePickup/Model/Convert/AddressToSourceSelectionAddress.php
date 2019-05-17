<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Convert;

use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface as PickupLocationsRequestAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface as SourceSelectionAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;

/**
 * Create Source Selection Address based on Pickup Locations Address request.
 */
class AddressToSourceSelectionAddress
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
     * @param PickupLocationsRequestAddressInterface $address
     *
     * @return SourceSelectionAddressInterface
     */
    public function execute(PickupLocationsRequestAddressInterface $address): SourceSelectionAddressInterface
    {
        $data = [
            'country' => $address->getCountry(),
            'postcode' => $address->getPostcode() ?? '',
            'region' => $address->getRegion() ?? '',
            'city' => $address->getCity() ??'',
            'street' => ''
        ];

        return $this->addressInterfaceFactory->create($data);
    }
}
