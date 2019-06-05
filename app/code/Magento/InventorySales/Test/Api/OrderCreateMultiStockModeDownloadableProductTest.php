<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Api;

/**
 * Web Api order creation with downloadable product in multi stock mode tests.
 */
class OrderCreateMultiStockModeDownloadableProductTest extends OrderPlacementBase
{
    /**
     * Create order with downloadable product - registered customer, default stock, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable_source_item_on_default_source.php
     */
    public function testCustomerPlaceOrderDefaultWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('downloadable-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Downloadable Product', $order['items'][0]['name']);
        $this->assertEquals('downloadable', $order['items'][0]['product_type']);
        $this->assertEquals('downloadable-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }

    /**
     * Create order with downloadable product - registered customer, default stock, additional website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable_source_item_on_default_source.php
     */
    public function testCustomerPlaceOrderCustomWebsiteDefaultStock()
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');
        $this->assignStockToWebsite(1, 'eu_website');
        $this->assignProductsToWebsite(['downloadable-product'], 'eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('downloadable-product');
        $this->estimateShippingCosts();
        $orderId = $this->submitPaymentInformation();
        $order = $this->getOrder($orderId);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals('Downloadable Product', $order['items'][0]['name']);
        $this->assertEquals('downloadable', $order['items'][0]['product_type']);
        $this->assertEquals('downloadable-product', $order['items'][0]['sku']);
        $this->assertEquals(10, $order['items'][0]['price']);
    }
}
