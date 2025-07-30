<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Address;

use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Handles the assignment of a pickup location to a given address by utilizing extension attributes.
 *
 * Set Address pickup location
 */
class SetAddressPickupLocation
{
    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $extensionFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        AddressExtensionInterfaceFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Set pickup location address
     *
     * @param AddressInterface $address
     * @param string $pickupLocation
     * @return void
     */
    public function execute(AddressInterface $address, string $pickupLocation): void
    {
        if ($address->getExtensionAttributes() === null) {
            $address->setExtensionAttributes($this->extensionFactory->create());
        }
        $address->getExtensionAttributes()->setPickupLocationCode($pickupLocation);
    }
}
