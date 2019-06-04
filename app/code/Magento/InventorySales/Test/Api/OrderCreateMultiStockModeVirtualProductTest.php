<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Api;

/**
 * Web Api order creation with virtual product in multi stock mode tests.
 */
class OrderCreateMultiStockModeVirtualProductTest extends OrderPlacementBase
{
    /**
     * Create order with virtual product - registered customer, default stock, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual_source_item_on_default_source.php
     */
    public function testCustomerPlaceOrderDefaultWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('virtual-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Virtual Product', $order['items'][0]['name']);
        $this->assertEquals('virtual', $order['items'][0]['product_type']);
        $this->assertEquals('virtual-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }

    /**
     * Create order with virtual product - registered customer, default stock, additional website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual_source_item_on_default_source.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     */
    public function testCustomerPlaceOrderAdditionalWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');
        $this->assignStockToWebsite(1, 'eu_website');
        $this->assignProductsToWebsite(['virtual-product'], 'eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('virtual-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Virtual Product', $order['items'][0]['name']);
        $this->assertEquals('virtual', $order['items'][0]['product_type']);
        $this->assertEquals('virtual-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }

    /**
     * Create order with virtual product - registered customer, additional stock, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual_source_item_on_additional_source.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCustomerPlaceOrderDefaultWebsiteAdditionalStock()
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(10, 'base');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('virtual-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Virtual Product', $order['items'][0]['name']);
        $this->assertEquals('virtual', $order['items'][0]['product_type']);
        $this->assertEquals('virtual-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }

    /**
     * Create order with virtual product - registered customer, additional stock, additional website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual_source_item_on_additional_source.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCustomerPlaceOrderAdditionalWebsiteAdditionalStock()
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');
        $this->assignProductsToWebsite(['virtual-product'], 'eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('virtual-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Virtual Product', $order['items'][0]['name']);
        $this->assertEquals('virtual', $order['items'][0]['product_type']);
        $this->assertEquals('virtual-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }
}
