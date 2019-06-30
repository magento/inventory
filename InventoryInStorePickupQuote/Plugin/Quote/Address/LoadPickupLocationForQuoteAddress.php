<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\Framework\Model\AbstractModel;
use Magento\InventoryInStorePickupQuote\Model\ResourceModel\GetPickupLocationCodeByQuoteAddressId;
use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\Quote\Address as AddressEntity;
use Magento\Quote\Model\ResourceModel\Quote\Address;

/**
 * Load Pickup Location Code for Quote Address.
 */
class LoadPickupLocationForQuoteAddress
{
    /**
     * @var GetPickupLocationCodeByQuoteAddressId
     */
    private $getPickupLocationCodeByQuoteAddressId;

    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * LoadPickupLocationForQuoteAddress constructor.
     *
     * @param GetPickupLocationCodeByQuoteAddressId $getPickupLocationCodeByQuoteAddressId
     * @param AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
     */
    public function __construct(
        GetPickupLocationCodeByQuoteAddressId $getPickupLocationCodeByQuoteAddressId,
        AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
    ) {
        $this->getPickupLocationCodeByQuoteAddressId = $getPickupLocationCodeByQuoteAddressId;
        $this->addressExtensionInterfaceFactory = $addressExtensionInterfaceFactory;
    }

    /**
     * Load and add Pickup Location information to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     * @param AbstractModel $entity
     *
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(Address $subject, Address $result, AbstractModel $entity): Address
    {
        if (!$entity->getId()) {
            return $result;
        }

        $pickupLocationCode = $this->getPickupLocationCodeByQuoteAddressId->execute((int)$entity->getId());
        if (!$pickupLocationCode) {
            return $result;
        }

        if (!$entity->getExtensionAttributes()) {
            $entity->setExtensionAttributes($this->addressExtensionInterfaceFactory->create());
        }

        $entity->getExtensionAttributes()->setPickupLocationCode($pickupLocationCode);

        return $result;
    }
}
