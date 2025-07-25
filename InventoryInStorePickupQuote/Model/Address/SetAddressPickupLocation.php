<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Address;

use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Sets the pickup location for a given address by utilizing extension attributes.
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
     * Set Address pickup location
     *
     * @param AddressInterface $address
     * @param string $pickupLocation
     *
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
