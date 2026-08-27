<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\CatalogInventory\Helper\Stock;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Helper\Stock;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @param string $storeCode
     * @param array $productsData
     *
     * @magentoDbIsolation disabled
     */
    #[DataProvider('assignStatusToProductDataProvider')]
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
     * @param string $storeCode
     * @param array $productsData
     *
     * @magentoDbIsolation disabled
     */
    #[DataProvider('assignStatusToProductDataProvider')]
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
    public static function assignStatusToProductDataProvider(): array
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

    #[
        DbIsolation(false),
        AppArea('frontend'),
        DataFixture(SourceFixture::class, as: 'src2'),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src2.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(ProductFixture::class, ['sku' => 'SKU-%uniqid%'], 'product'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$product.sku$', 'source_code' => 'default', 'quantity' => 0],
                ['sku' => '$product.sku$', 'source_code' => '$src2.source_code$', 'quantity' => 100],
            ]
        ),
    ]
    public function testAssignStatusToProductAfterChangingSkuCase(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $sku = $fixtures->get('product')->getSku();

        // Check that product is salable before SKU case change
        $product = $this->productRepository->get($sku);
        $this->stockHelper->assignStatusToProduct($product);

        self::assertTrue($product->isSalable());

        // Update SKU to lowercase
        $product = $this->productRepository->get($sku, true, Store::DEFAULT_STORE_ID, true);
        $product->setSku(strtolower($sku));
        $this->productRepository->save($product);

        // Check that product is salable after SKU case change
        $product = $this->productRepository->get(strtolower($sku));
        $this->stockHelper->assignStatusToProduct($product);

        self::assertTrue($product->isSalable());
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
