<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Api;

/**
 * Web Api order creation with configurable product in multi stock mode tests.
 */
class OrderCreateMultiStockModeConfigurableProductTest extends OrderPlacementBase
{
    /**
     * Create order with configurable product - registered customer, default stock, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/default_stock_configurable_products.php
     */
    public function testCustomerPlaceOrderDefaultWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addConfigurableProduct('configurable_in_stock');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Configurable Product In Stock', $order['items'][0]['name']);
        $this->assertEquals('configurable', $order['items'][0]['product_type']);
        $this->assertEquals('simple_10', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals('Configurable OptionOption 1', $order['items'][1]['name']);
        $this->assertEquals('simple', $order['items'][1]['product_type']);
        $this->assertEquals('simple_10', $order['items'][1]['sku']);
        $this->assertEquals(0, $order['items'][1]['price']);
    }

    /**
     * Create order with configurable product - registered customer, default stock, additional website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/default_stock_configurable_products.php
     */
    public function testCustomerPlaceOrderAdditionalWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');
        $this->assignStockToWebsite(1, 'eu_website');
        $this->assignProductsToWebsite(['configurable_in_stock', 'simple_10'], 'eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addConfigurableProduct('configurable_in_stock');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Configurable Product In Stock', $order['items'][0]['name']);
        $this->assertEquals('configurable', $order['items'][0]['product_type']);
        $this->assertEquals('simple_10', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals('Configurable OptionOption 1', $order['items'][1]['name']);
        $this->assertEquals('simple', $order['items'][1]['product_type']);
        $this->assertEquals('simple_10', $order['items'][1]['sku']);
        $this->assertEquals(0, $order['items'][1]['price']);
    }

    /**
     * Create order with configurable product - registered customer, additional stock, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/source_items_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCustomerPlaceOrderDefaultWebsiteAdditionalStock()
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(20, 'base');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addConfigurableProduct('configurable');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Configurable Product', $order['items'][0]['name']);
        $this->assertEquals('configurable', $order['items'][0]['product_type']);
        $this->assertEquals('simple_10', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals('Configurable OptionOption 1', $order['items'][1]['name']);
        $this->assertEquals('simple', $order['items'][1]['product_type']);
        $this->assertEquals('simple_10', $order['items'][1]['sku']);
        $this->assertEquals(0, $order['items'][1]['price']);
    }

    /**
     * Create order with configurable product - registered customer, additional stock, additional website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/source_items_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCustomerPlaceOrderAdditionalWebsiteAdditionalStock()
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_us_website');
        $this->assignProductsToWebsite(['configurable_in_stock', 'simple_10'], 'us_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addConfigurableProduct('configurable');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Configurable Product', $order['items'][0]['name']);
        $this->assertEquals('configurable', $order['items'][0]['product_type']);
        $this->assertEquals('simple_10', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals('Configurable OptionOption 1', $order['items'][1]['name']);
        $this->assertEquals('simple', $order['items'][1]['product_type']);
        $this->assertEquals('simple_10', $order['items'][1]['sku']);
        $this->assertEquals(0, $order['items'][1]['price']);
    }
}
