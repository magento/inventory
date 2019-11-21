<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Copy;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupSales\Model\ExtractSourceAddressData;
use Magento\InventoryInStorePickupQuote\Model\GetShippingAddressData;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\AddressFactory;

/**
 * Convert Source and provided Shipping Address to Source Quote Address.
 */
class SourceToQuoteAddress
{
    /**
     * @var ExtractSourceAddressData
     */
    private $extractSourceAddressData;

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
     * @param ExtractSourceAddressData $extractSourceAddressData
     * @param AddressFactory $addressFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param GetShippingAddressData $getShippingAddressData
     */
    public function __construct(
        Copy $objectCopyService,
        ExtractSourceAddressData $extractSourceAddressData,
        AddressFactory $addressFactory,
        DataObjectHelper $dataObjectHelper,
        GetShippingAddressData $getShippingAddressData
    ) {
        $this->extractSourceAddressData = $extractSourceAddressData;
        $this->objectCopyService = $objectCopyService;
        $this->addressFactory = $addressFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->getShippingAddressData = $getShippingAddressData;
    }

    /**
     * Convert Source Data and Quote Address to Source Quote Address.
     *
     * @param SourceInterface $source
     * @param AddressInterface $originalAddress
     * @param array $data
     *
     * @return AddressInterface
     */
    public function convert(
        SourceInterface $source,
        AddressInterface $originalAddress,
        $data = []
    ): AddressInterface {
        $sourceAddressData = $this->getShippingAddressData->execute()
            + $this->extractSourceAddressData->execute($source);

        $quoteAddressData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_in_store_pickup_shipping_address',
            $originalAddress
        );

        $address = $this->addressFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $address,
            array_merge($sourceAddressData, $quoteAddressData, $data),
            AddressInterface::class
        );

        return $address;
    }
}
