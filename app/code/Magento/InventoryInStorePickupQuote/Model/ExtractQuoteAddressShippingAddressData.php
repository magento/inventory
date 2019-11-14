<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\DataObject\Copy;
use Magento\Quote\Api\Data\AddressInterface;

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

        // TODO: temporary solution to avoid issue with config merge.
        $data['customer_address_id'] = $address->getCustomerAddressId();

        if (isset($data[AddressInterface::KEY_STREET]) && is_array($data[AddressInterface::KEY_STREET])) {
            $data[AddressInterface::KEY_STREET] = implode("\n", $data[AddressInterface::KEY_STREET]);
        }

        return $data;
    }
}
