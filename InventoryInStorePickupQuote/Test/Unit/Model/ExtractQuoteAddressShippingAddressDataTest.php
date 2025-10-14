<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Unit\Model;

use Magento\Framework\DataObject\Copy;
use Magento\InventoryInStorePickupQuote\Model\ExtractQuoteAddressShippingAddressData;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\Data\AddressInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for ExtractQuoteAddressShippingAddressData
 */
class ExtractQuoteAddressShippingAddressDataTest extends TestCase
{
    /**
     * @var ExtractQuoteAddressShippingAddressData
     */
    private $extractor;

    /**
     * @var Copy|MockObject
     */
    private $objectCopyServiceMock;

    /**
     * @var AddressInterface|MockObject
     */
    private $addressMock;

    protected function setUp(): void
    {
        $this->objectCopyServiceMock = $this->createMock(Copy::class);
        $this->addressMock = $this->createMock(AddressInterface::class);
        $this->extractor = new ExtractQuoteAddressShippingAddressData(
            $this->objectCopyServiceMock
        );
    }

    /**
     * Test execute method to verify specific data values
     */
    public function testExecuteWithValidAddressData(): void
    {
        $addressData = [
            AddressInterface::KEY_STREET => ['123 Main St', 'Apt 4B'],
            'some_field' => 'some_value'
        ];
        $this->objectCopyServiceMock
            ->method('getDataFromFieldset')
            ->with('sales_convert_quote_address', 'shipping_address_data', $this->addressMock)
            ->willReturn($addressData);
        $result = $this->extractor->execute($this->addressMock);
        $expectedData = [
            AddressInterface::KEY_STREET => "123 Main St\nApt 4B",
            'some_field' => 'some_value',
            AddressInterface::SAME_AS_BILLING => false,
            AddressInterface::SAVE_IN_ADDRESS_BOOK => false,
            AddressInterface::CUSTOMER_ADDRESS_ID => null,
            'shipping_method' => InStorePickup::DELIVERY_METHOD
        ];
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test execute method with non-array street field
     */
    public function testExecuteWithNonArrayStreetField(): void
    {
        $addressData = [
            AddressInterface::KEY_STREET => '123 Main St',
            'some_field' => 'some_value'
        ];
        $this->objectCopyServiceMock
            ->method('getDataFromFieldset')
            ->with('sales_convert_quote_address', 'shipping_address_data', $this->addressMock)
            ->willReturn($addressData);
        $result = $this->extractor->execute($this->addressMock);
        $expectedData = [
            AddressInterface::KEY_STREET => '123 Main St',
            'some_field' => 'some_value',
            AddressInterface::SAME_AS_BILLING => false,
            AddressInterface::SAVE_IN_ADDRESS_BOOK => false,
            AddressInterface::CUSTOMER_ADDRESS_ID => null,
            'shipping_method' => InStorePickup::DELIVERY_METHOD
        ];
        $this->assertEquals($expectedData, $result);
    }
}
