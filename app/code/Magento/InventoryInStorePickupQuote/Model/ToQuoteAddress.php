<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickup\Model\PickupLocation;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;

/**
 * Convert Pickup Location and provided Shipping Address to Pickup Location Quote Address.
 */
class ToQuoteAddress
{
    /**
     * @var ExtractPickupLocationShippingAddressData
     */
    private $extractPickupLocationShippingAddressData;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    private $addressFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param ExtractPickupLocationShippingAddressData $extractPickupLocationShippingAddressData
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Copy $objectCopyService,
        ExtractPickupLocationShippingAddressData $extractPickupLocationShippingAddressData,
        AddressFactory $addressFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->extractPickupLocationShippingAddressData = $extractPickupLocationShippingAddressData;
        $this->objectCopyService = $objectCopyService;
        $this->addressFactory = $addressFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Convert Pickup Location and Quote Address to Pickup Location Quote Address.
     *
     * @param PickupLocationInterface $pickupLocation
     * @param Address $originalAddress
     *
     * @param array $data
     *
     * @return AddressInterface
     */
    public function convert(
        PickupLocationInterface $pickupLocation,
        Address $originalAddress,
        $data = []
    ): AddressInterface {
        $pickupLocationAddressData = $this->extractPickupLocationShippingAddressData->execute($pickupLocation);

        $quoteAddressData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_pickup_location_shipping_address',
            $originalAddress
        );

        $address = $this->addressFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $address,
            array_merge($pickupLocationAddressData, $quoteAddressData, $data),
            AddressInterface::class
        );

        $address->setShippingMethod(InStorePickup::DELIVERY_METHOD);

        return $address;
    }
}
