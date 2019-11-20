<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickup\Model\ExtractPickupLocationAddressData;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\AddressFactory;

/**
 * Convert Pickup Location and provided Shipping Address to Pickup Location Quote Address.
 */
class ToQuoteAddress
{
    /**
     * @var ExtractPickupLocationAddressData
     */
    private $extractPickupLocationShippingAddressData;

    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var GetShippingAddressData
     */
    private $getShippingAddressData;

    /**
     * @param Copy $objectCopyService
     * @param ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData
     * @param AddressFactory $addressFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param GetShippingAddressData $getShippingAddressData
     */
    public function __construct(
        Copy $objectCopyService,
        ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData,
        AddressFactory $addressFactory,
        DataObjectHelper $dataObjectHelper,
        GetShippingAddressData $getShippingAddressData
    ) {
        $this->extractPickupLocationShippingAddressData = $extractPickupLocationShippingAddressData;
        $this->objectCopyService = $objectCopyService;
        $this->addressFactory = $addressFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->getShippingAddressData = $getShippingAddressData;
    }

    /**
     * Convert Pickup Location Data and Quote Address to Pickup Location Quote Address.
     *
     * @param PickupLocationInterface $pickupLocation
     * @param AddressInterface $originalAddress
     * @param array $data
     *
     * @return AddressInterface
     */
    public function convert(
        PickupLocationInterface $pickupLocation,
        AddressInterface $originalAddress,
        $data = []
    ): AddressInterface {
        $pickupLocationAddressData = $this->getShippingAddressData->execute()
            + $this->extractPickupLocationShippingAddressData->execute($pickupLocation);

        $quoteAddressData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_in_store_pickup_shipping_address',
            $originalAddress
        );

        $address = $this->addressFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $address,
            array_merge($pickupLocationAddressData, $quoteAddressData, $data),
            AddressInterface::class
        );

        return $address;
    }
}
