<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\InventoryInStorePickupQuote\Model\ResourceModel\DeleteQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupQuote\Model\ResourceModel\SaveQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Model\Quote\Address;

/**
 * Save or delete selected Pickup Location Code for Quote Address.
 */
class ManageAssignmentOfPickupLocationToQuoteAddress
{
    /**
     * @var SaveQuoteAddressPickupLocation
     */
    private $saveQuoteAddressPickupLocation;

    /**
     * @var DeleteQuoteAddressPickupLocation
     */
    private $deleteQuoteAddressPickupLocation;

    /**
     * @param SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation
     * @param DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation
     */
    public function __construct(
        SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation,
        DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation
    ) {
        $this->saveQuoteAddressPickupLocation = $saveQuoteAddressPickupLocation;
        $this->deleteQuoteAddressPickupLocation = $deleteQuoteAddressPickupLocation;
    }

    /**
     * Save information about associate Pickup Location Code to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     *
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAfterSave(Address $subject, Address $result): Address
    {
        if (!$this->validateAddress($subject)) {
            return $result;
        }

        if (!$this->isAddressHasPickupLocation($subject)) {
            $this->deleteQuoteAddressPickupLocation->execute((int)$subject->getId());

            return $result;
        }

        $this->saveQuoteAddressPickupLocation->execute(
            (int)$subject->getId(),
            $subject->getExtensionAttributes()->getPickupLocationCode()
        );

        return $result;
    }

    /**
     * Validate if address has Pickup Location.
     *
     * @param Address $address
     *
     * @return bool
     */
    private function isAddressHasPickupLocation(Address $address): bool
    {
        return $address->getShippingMethod() === InStorePickup::DELIVERY_METHOD &&
            $address->getExtensionAttributes()->getPickupLocationCode();
    }

    /**
     * Check if address can have a Pickup Location.
     *
     * @param Address $address
     *
     * @return bool
     */
    private function validateAddress(Address $address): bool
    {
        return $address->getExtensionAttributes() && $address->getAddressType() === Address::ADDRESS_TYPE_SHIPPING;
    }
}
