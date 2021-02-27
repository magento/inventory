<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Options;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableView;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class OptionsAvailabilityTest extends TestCase
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ConfigurableView
     */
    private $configurableView;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->configurableView = Bootstrap::getObjectManager()->get(ConfigurableView::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->serializer = Bootstrap::getObjectManager()->get(SerializerInterface::class);
    }

    /**
     * @codingStandardsIgnoreStart
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @codingStandardsIgnoreEnd
     * @dataProvider getSalableOptionsDataProvider
     * @param string $storeCode
     * @param int $expected
     * @return void
     *
     * @magentoDbIsolation disabled
     */
    public function testGetSalableOptions(string $storeCode, int $expected)
    {
        $this->storeManager->setCurrentStore($storeCode);

        $configurableProduct = $this->productRepository->get('configurable', false, null, true);
        $this->configurableView->setProduct($configurableProduct);
        $result = $this->serializer->unserialize($this->configurableView->getJsonConfig());
        $attributes = reset($result['attributes']);
        $actual = count($attributes['options'] ?? []);

        $this->assertEquals(
            $expected,
            $actual
        );
    }

    /**
     * Verifies that out of stock options are displayed with show out of stock enabled
     *
     * @codingStandardsIgnoreStart
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_product_configurable_out_of_stock_all.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture store_for_us_website_store cataloginventory/options/show_out_of_stock 1
     * @magentoDbIsolation disabled
     * @codingStandardsIgnoreEnd
     * @return void
     */
    public function testGetSalableOptionsWithShowOutOfStock(): void
    {
        $this->storeManager->setCurrentStore('store_for_us_website');

        $configurableProduct = $this->productRepository->get('configurable', false, null, true);
        $this->configurableView->setProduct($configurableProduct);
        $result = $this->serializer->unserialize($this->configurableView->getJsonConfig());
        $attributes = reset($result['attributes']);
        $actual = count($attributes['options'] ?? []);

        $this->assertEquals(2, $actual);
    }

    /**
     * @return array
     */
    public function getSalableOptionsDataProvider()
    {
        return [
            [
                'store_for_eu_website',
                0
            ],
            [
                'store_for_us_website',
                2
            ],
        ];
    }
}
