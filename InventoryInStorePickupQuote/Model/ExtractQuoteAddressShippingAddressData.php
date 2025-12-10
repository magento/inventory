<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\DataObject\Copy;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;

/**
 * Extract quote address details according to fieldset config.
 */
class ExtractQuoteAddressShippingAddressData
{
    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @param Copy $copyService
     */
    public function __construct(Copy $copyService)
    {
        $this->objectCopyService = $copyService;
    }

    /**
     * Extract data from Quote Address.
     *
     * @param AddressInterface $address
     *
     * @return array
     */
    public function execute(AddressInterface $address): array
    {
        $data = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'shipping_address_data',
            $address
        );
        if (isset($data[AddressInterface::KEY_STREET]) && is_array($data[AddressInterface::KEY_STREET])) {
            $data[AddressInterface::KEY_STREET] = implode("\n", $data[AddressInterface::KEY_STREET]);
        }
        $data[AddressInterface::SAME_AS_BILLING] = false;
        $data[AddressInterface::SAVE_IN_ADDRESS_BOOK] = false;
        $data[AddressInterface::CUSTOMER_ADDRESS_ID] = null;
        $data['shipping_method'] = InStorePickup::DELIVERY_METHOD;

        return $data;
    }
}
