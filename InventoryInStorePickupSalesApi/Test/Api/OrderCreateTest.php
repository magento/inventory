<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Test\Api;

use Magento\Customer\Api\Data\AddressInterface;

class OrderCreateTest extends OrderPlacementBase
{
    /**
     * Create order  - registered customer, existed address, `eu-1` pickup location.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return void
     */
    public function testPlaceOrderRegisteredCustomerExistedAddress(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignCustomerToCustomWebsite('customer@example.com', 'eu_website');
        $customer = $this->getCustomerByEmail('customer@example.com', 'eu_website');
        $this->assignAddressToTheCustomer((int)$customer->getId());
        $this->setStoreView('store_for_eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('SKU-1');
        $customer = $this->getCustomerByEmail('customer@example.com', 'eu_website');
        $this->estimateShippingCostsByAddressId($customer->getDefaultShipping());
        $this->setShippingAndBillingInformation($customer->getDefaultShipping());
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
        $this->cancelOrder($orderId);
    }

    /**
     * Create order  - guest customer, `eu-1` pickup location.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderGuest(): void
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');

        // create guest customer cart;
        $this->customerToken = null;
        $this->createCustomerCart();

        $this->addProduct('SKU-1');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();

        $orderId = $this->submitPaymentInformation();

        $this->verifyCreatedOrder($orderId);
        $this->cancelOrder($orderId);
    }

    /**
     * Create order  - guest customer, billing same as shipping, `eu-1` pickup location.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderGuestBillingAddressSameAsShipping(): void
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');

        // create guest customer cart;
        $this->customerToken = null;
        $this->createCustomerCart();

        $this->addProduct('SKU-1');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();

        $orderId = $this->submitPaymentInformation(true);

        $this->verifyCreatedOrder($orderId);
        $this->cancelOrder($orderId);
    }

    /**
     * Create order  - registered customer, new address, `eu-1` pickup location.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderRegisteredCustomerNewAddress(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignCustomerToCustomWebsite('customer@example.com', 'eu_website');
        $this->setStoreView('store_for_eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('SKU-1');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
        $this->cancelOrder($orderId);
    }

    /**
     * Create order  - registered customer, existed address, save new address in address book, `eu-1` pickup location.
     * NO NEW address should be added to address book.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return void
     */
    public function testPlaceOrderRegisteredCustomerExistedAddressSaveNewAddressInAddressBook(): void
    {
        $this->_markTestAsRestOnly();

        $this->setStoreView('store_for_eu_website');
        $this->assignCustomerToCustomWebsite('customer@example.com', 'eu_website');
        $customer = $this->getCustomerByEmail('customer@example.com', 'eu_website');
        $this->assignAddressToTheCustomer((int)$customer->getId());

        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('SKU-1');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation(null, true);
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);

        $addressList = $this->getCustomerAddressList('eu_website');
        // make sure that NO NEW address has been added to address book;
        $this->assertCount(1, $addressList);

        // make sure existed address has not been changed;/*
        /** @var AddressInterface $address */
        $address = current($addressList);
        $this->assertAddressData($this->getBaseAddressData(), $address);

        $this->cancelOrder($orderId);
    }

    /**
     * Create order  - registered customer, new address (save in address book), `eu-1` pickup location.
     * NO address should be added to address book.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderRegisteredCustomerNewAddressSaveInAddressBook(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignCustomerToCustomWebsite('customer@example.com', 'eu_website');
        $this->setStoreView('store_for_eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('SKU-1');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation(null, true);
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);

        $addressList = $this->getCustomerAddressList('eu_website');

        // make sure that NO address has been added to address book;
        $this->assertCount(0, $addressList);

        $this->cancelOrder($orderId);
    }

    /**
     * Verify created order is correct.
     *
     * @param int $orderId
     * @return void
     */
    private function verifyCreatedOrder(int $orderId): void
    {
        $order = $this->getOrder($orderId);

        // assert order
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Simple Product 1 Orange', $order['items'][0]['name']);
        $this->assertEquals('simple', $order['items'][0]['product_type']);
        $this->assertEquals('SKU-1', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);

        // assert billing address
        $expectedBillingAddress = $this->getBaseAddressData();
        $expectedBillingAddress['address_type'] = 'billing';

        $actualBillingAddress = $order['billing_address'];
        unset($actualBillingAddress['entity_id']);
        unset($actualBillingAddress['parent_id']);
        unset($actualBillingAddress['customer_address_id']);
        $this->assertEquals($expectedBillingAddress, $actualBillingAddress);

        //assert shipping assignment address
        /**
         * @var $expectedShippingAssignmentAddress
         * @see app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php:16
         */
        $expectedShippingAssignmentAddress = [
            'address_type' => 'shipping',
            'city' => 'Mitry-Mory',
            'country_id' => 'FR',
            'email' => 'customer@example.com',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'postcode' => '77292 CEDEX',
            'region' => 'Seine-et-Marne',
            'region_code' => '77',
            'region_id' => 259,
            'street' => [
                'Rue Paul Vaillant Couturier 31'
            ],
            'telephone' => '(555) 555-5555'
        ];

        $this->assertTrue(isset($order['extension_attributes']['shipping_assignments'][0]['shipping']['address']));

        $shippingAssignmentAddress = $order['extension_attributes']['shipping_assignments'][0]['shipping']['address'];
        unset($shippingAssignmentAddress['entity_id']);
        unset($shippingAssignmentAddress['parent_id']);
        $this->assertEquals(
            $shippingAssignmentAddress,
            $expectedShippingAssignmentAddress
        );
    }

    /**
     * Assert that address data is equal;
     *
     * @param array $expectedAddressData
     * @param AddressInterface $actualAddress
     */
    private function assertAddressData(array $expectedAddressData, AddressInterface $actualAddress): void
    {
        $this->assertEquals($expectedAddressData['region'], $actualAddress->getRegion()->getRegion());
        $this->assertEquals($expectedAddressData['region_id'], $actualAddress->getRegion()->getRegionId());
        $this->assertEquals($expectedAddressData['region_code'], $actualAddress->getRegion()->getRegionCode());
        $this->assertEquals($expectedAddressData['country_id'], $actualAddress->getCountryId());
        $this->assertEquals($expectedAddressData['street'], $actualAddress->getStreet());
        $this->assertEquals($expectedAddressData['postcode'], $actualAddress->getPostcode());
        $this->assertEquals($expectedAddressData['city'], $actualAddress->getCity());
        $this->assertEquals($expectedAddressData['firstname'], $actualAddress->getFirstname());
        $this->assertEquals($expectedAddressData['lastname'], $actualAddress->getLastname());
        $this->assertEquals($expectedAddressData['telephone'], $actualAddress->getTelephone());
    }
}
