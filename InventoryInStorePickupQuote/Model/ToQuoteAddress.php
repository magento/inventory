<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Copy;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;

/**
 * Convert Pickup Location and provided Shipping Address to Pickup Location Quote Address.
 *
 * @api
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
     * Convert Pickup Location Data and Quote Address to Pickup Location Quote Address.
     *
     * @param array $pickupLocationAddressData
     * @param Address $originalAddress
     * @param array $data
     *
     * @return AddressInterface
     */
    public function convert(
        array $pickupLocationAddressData,
        Address $originalAddress,
        $data = []
    ): AddressInterface {
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

        return $address;
    }
}
