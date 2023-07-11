<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\CatalogInventory\Helper\Stock;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AssignStatusToProductTest extends TestCase
{
    /**
     * @var Stock
     */
    private $stockHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stockHelper = Bootstrap::getObjectManager()->get(Stock::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @dataProvider assignStatusToProductDataProvider
     * @param string $storeCode
     * @param array $productsData
     *
     * @magentoDbIsolation disabled
     */
    public function testAssignStatusToProductIfStatusParameterIsNotPassed(string $storeCode, array $productsData)
    {
        $storeId = $this->storeManager->getStore($storeCode)->getId();

        foreach ($productsData as $sku => $expectedStatus) {
            $product = $this->productRepository->get($sku, false, $storeId, forceReload: true);
            /** @var Product $product */
            $this->stockHelper->assignStatusToProduct($product);

            self::assertEquals($expectedStatus, $product->isSalable());
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @dataProvider assignStatusToProductDataProvider
     * @param string $storeCode
     * @param array $productsData
     *
     * @magentoDbIsolation disabled
     */
    public function testAssignStatusToProductIfStatusParameterIsPassed(string $storeCode, array $productsData)
    {
        $expectedStatus = 1;
        $storeId = $this->storeManager->getStore($storeCode)->getId();

        foreach (array_keys($productsData) as $sku) {
            $product = $this->productRepository->get($sku, false, $storeId, forceReload: true);
            /** @var Product $product */
            $this->stockHelper->assignStatusToProduct($product, $expectedStatus);

            self::assertEquals($expectedStatus, $product->isSalable());
        }
    }

    /**
     * @return array
     */
    public function assignStatusToProductDataProvider(): array
    {
        return [
            'eu_website' => [
                'store_for_eu_website',
                [
                    'SKU-1' => 1,
                    'SKU-2' => 0,
                    'SKU-3' => 0,
                ],
            ],
            'us_website' => [
                'store_for_us_website',
                [
                    'SKU-1' => 0,
                    'SKU-2' => 1,
                    'SKU-3' => 0,
                ],
            ],
            'global_website' => [
                'store_for_global_website',
                [
                    'SKU-1' => 1,
                    'SKU-2' => 1,
                    'SKU-3' => 0,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->storeManager->setCurrentStore($this->storeCodeBefore);

        parent::tearDown();
    }
}
