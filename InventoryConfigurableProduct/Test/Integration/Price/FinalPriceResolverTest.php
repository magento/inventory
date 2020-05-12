<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Price;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurablePriceResolver;
use Magento\ConfigurableProduct\Pricing\Price\FinalPriceResolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class FinalPriceResolverTest extends TestCase
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
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @var  ConfigurablePriceResolver
     */
    private $configurablePriceResolver;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();
        $finalPrice = Bootstrap::getObjectManager()->get(FinalPriceResolver::class);

        $this->configurablePriceResolver = Bootstrap::getObjectManager()->create(
            ConfigurablePriceResolver::class,
            ['priceResolver' => $finalPrice]
        );
    }

    // @codingStandardsIgnoreStart
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
     * @return void
     *
     * @magentoDbIsolation disabled
     */
    // @codingStandardsIgnoreEnd
    public function testResolvePriceWithAllChildren()
    {
        $this->storeManager->setCurrentStore('store_for_us_website');

        $configurableProduct = $this->productRepository->get('configurable', false, null, true);
        $actualPrice = $this->configurablePriceResolver->resolvePrice($configurableProduct);

        self::assertEquals(10, $actualPrice);
    }

    /// @codingStandardsIgnoreStart
    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_product_configurable_out_of_stock.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @return void
     *
     * @magentoDbIsolation disabled
     */
    // @codingStandardsIgnoreEnd
    public function testResolvePriceIfOneOfChildIsOutOfStock()
    {
        $this->storeManager->setCurrentStore('store_for_us_website');

        $configurableProduct = $this->productRepository->get('configurable', false, null, true);
        $actualPrice = $this->configurablePriceResolver->resolvePrice($configurableProduct);
        self::assertEquals(20, $actualPrice);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (null !== $this->storeCodeBefore) {
            $this->storeManager->setCurrentStore($this->storeCodeBefore);
        }
    }
}
