<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Helper\Stock\AdaptAssignStatusToProductPlugin;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class AdaptAssignStatusToProductPluginTest extends TestCase
{
    /**
     * @var AdaptAssignStatusToProductPlugin
     */
    private $subject;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Bootstrap::getObjectManager()->get(AdaptAssignStatusToProductPlugin::class);
    }

    /**
     * Test that out of stock Configurable product with options, one of which is out of stock, stays Out of stock
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable_12345.php
     * @return void
     */
    public function testBeforeAssignStatusToProduct(): void
    {
        $stock = Bootstrap::getObjectManager()->get(\Magento\CatalogInventory\Helper\Stock::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);

        $configurable = $productRepository->get('12345');
        $configurable->setQuantityAndStockStatus(['is_in_stock' => false]);
        $configurable->save();
        $option = $productRepository->get('simple_30');
        $option->setQuantityAndStockStatus(['is_in_stock' => false]);
        $option->save();
        $result = $this->subject->beforeAssignStatusToProduct($stock, $configurable, null);
        $this->assertEquals(null, $result[1]);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @throws NoSuchEntityException
     * @return void
     */
    public function testStockStatusShouldBeResolvedBasedOnProductStoreId(): void
    {
        $stockHelper = Bootstrap::getObjectManager()->get(Stock::class);
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $sku = 'configurable';
        $expected = [
            'store_for_eu_website' => 0,
            'store_for_us_website' => 1,
            'store_for_global_website' => 1,
        ];
        $actual = [];
        foreach (array_keys($expected) as $storeCode) {
            $storeId = $storeManager->getStore($storeCode)->getId();
            $product = $productRepository->get($sku, false, $storeId, forceReload: true);
            $result = $this->subject->beforeAssignStatusToProduct($stockHelper, $product);
            $actual[$storeCode] = $result[1];
        }

        self::assertEquals($expected, $actual);
    }
}
