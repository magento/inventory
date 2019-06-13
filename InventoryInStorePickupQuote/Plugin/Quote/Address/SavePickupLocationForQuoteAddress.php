<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\InventoryInStorePickupQuote\Model\ResourceModel\SaveQuoteAddressPickupLocation;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address;

/**
 * Save selected Pickup Location Code for Quote Address.
 */
class SavePickupLocationForQuoteAddress
{
    /**
     * @var SaveQuoteAddressPickupLocation
     */
    private $saveQuoteAddressPickupLocation;

    /**
     * @param SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation
     */
    public function __construct(SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation)
    {
        $this->saveQuoteAddressPickupLocation = $saveQuoteAddressPickupLocation;
    }

    /**
     * Save information about associate Pickup Location Code to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     * @param AddressInterface $entity
     *
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Address $subject, Address $result, AddressInterface $entity): Address
    {
        if ($entity->getExtensionAttributes() && $entity->getExtensionAttributes()->getPickupLocationCode()) {
            $this->saveQuoteAddressPickupLocation->execute(
                (int)$entity->getId(),
                $entity->getExtensionAttributes()->getPickupLocationCode()
            );
        }

        return $result;
    }
}
