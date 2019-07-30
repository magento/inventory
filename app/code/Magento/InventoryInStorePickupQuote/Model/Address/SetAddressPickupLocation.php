<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Address;

use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Set Address pickup location
 * @api
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
