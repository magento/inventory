<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Api;

/**
 * Web Api order create in single stock mode tests.
 */
class OrderCreateSingleStockModeTest extends OrderPlacementBase
{
    /**
     * Create order with different types of products - registered customer, single stock mode, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testCustomerPlaceOrderWithDifferentProductTypesDefaultWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('simple-product');
        $this->addProduct('virtual-product');
        $this->addProduct('downloadable-product');
        $this->addConfigurableProduct('configurable');
        $this->addBundleProduct('bundle');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - registered customer, single stock mode, custom website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testCustomerPlaceOrderWithDifferentProductTypesCustomWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $websiteCode = 'eu_website';
        $products = [
            'simple-product',
            'downloadable-product',
            'virtual-product',
            'configurable',
            'simple_10',
            'bundle',
            'simple_product_bundle_option'
        ];
        $this->assignCustomerToCustomWebsite('customer@example.com', $websiteCode);
        $this->assignProductsToWebsite($products, $websiteCode);
        $this->setStoreView('store_for_eu_website');
        $this->getCustomerToken('customer@example.com', 'password');
        $this->createCustomerCart();
        $this->addProduct('simple-product');
        $this->addProduct('virtual-product');
        $this->addProduct('downloadable-product');
        $this->addConfigurableProduct('configurable');
        $this->addBundleProduct('bundle');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - guest customer, single stock mode, default website.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testGuestPlaceOrderWithDifferentProductTypesDefaultWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $this->createCustomerCart();
        $this->addProduct('simple-product');
        $this->addProduct('virtual-product');
        $this->addProduct('downloadable-product');
        $this->addConfigurableProduct('configurable');
        $this->addBundleProduct('bundle');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - guest customer, single stock mode, default website.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySales/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testGuestPlaceOrderWithDifferentProductTypesCustomWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $websiteCode = 'eu_website';
        $products = [
            'simple-product',
            'downloadable-product',
            'virtual-product',
            'configurable',
            'simple_10',
            'bundle',
            'simple_product_bundle_option'
        ];
        $this->assignProductsToWebsite($products, $websiteCode);
        $this->setStoreView('store_for_eu_website');
        $this->createCustomerCart();
        $this->addProduct('simple-product');
        $this->addProduct('virtual-product');
        $this->addProduct('downloadable-product');
        $this->addConfigurableProduct('configurable');
        $this->addBundleProduct('bundle');
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Verify, created order is correct.
     *
     * @param int $orderId
     * @return void
     */
    private function verifyCreatedOrder(int $orderId): void
    {
        $order = $this->getOrder($orderId);
        $this->assertGreaterThan(0, $order['increment_id']);
        $this->assertEquals('customer@example.com', $order['customer_email']);

        $this->assertEquals('simple-product', $order['items'][0]['sku']);
        $this->assertEquals('simple', $order['items'][0]['product_type']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals(1, $order['items'][0]['qty_ordered']);

        $this->assertEquals('virtual-product', $order['items'][1]['sku']);
        $this->assertEquals('virtual', $order['items'][1]['product_type']);
        $this->assertEquals(10, $order['items'][1]['price']);
        $this->assertEquals(1, $order['items'][1]['qty_ordered']);

        $this->assertEquals('downloadable-product', $order['items'][2]['sku']);
        $this->assertEquals('downloadable', $order['items'][2]['product_type']);
        $this->assertEquals(10, $order['items'][2]['price']);
        $this->assertEquals(1, $order['items'][2]['qty_ordered']);

        $this->assertEquals('simple_10', $order['items'][3]['sku']);
        $this->assertEquals('configurable', $order['items'][3]['product_type']);
        $this->assertEquals(10, $order['items'][3]['price']);
        $this->assertEquals(1, $order['items'][3]['qty_ordered']);

        $this->assertEquals('bundle-simple_product_bundle_option', $order['items'][5]['sku']);
        $this->assertEquals('bundle', $order['items'][5]['product_type']);
        $this->assertEquals(10, $order['items'][5]['price']);
        $this->assertEquals(1, $order['items'][5]['qty_ordered']);
    }
}
