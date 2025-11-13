<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Import;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\ProductFactory;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\ImportExport\Test\Fixture\CsvFile as CsvFileFixture;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsCache;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\InventorySales\Model\GetProductSalableQty;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/2219820
 *
 * @magentoDbIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ProductFactory
     */
    private $productImporterFactory;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var string[]
     */
    private $importedProducts;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var DeleteSourceItemsBySkus
     */
    private $consumer;

    /**
     * @var GetBySku
     */
    private $getBySku;

    /** @var ConsumerFactory */
    private $consumerFactory;

    /**
     * Setup Test for Product Import
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->defaultSourceProvider = $this->objectManager->get(DefaultSourceProviderInterface::class);
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->productImporterFactory = $this->objectManager->get(ProductFactory::class);
        $this->searchCriteriaBuilderFactory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $this->sourceItemRepository = $this->objectManager->get(SourceItemRepositoryInterface::class);
        $this->messageEncoder = $this->objectManager->get(MessageEncoder::class);
        $this->consumer = $this->objectManager->get(DeleteSourceItemsBySkus::class);
        $this->getBySku = $this->objectManager->get(GetBySku::class);
        $this->consumerFactory = $this->objectManager->get(ConsumerFactory::class);
    }

    /**
     * Test that following a Product Import Source Item is created as expected
     *
     * @return void
     */
    public function testSourceItemCreatedOnProductImport(): void
    {
        $pathToFile = __DIR__ . '/_files/product_import.csv';
        /** @var Product $productImporterModel */
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();
        $sku = 'example_simple_for_source_item';
        $compareData = $this->buildDataArray(
            $this->getSourceItemList('example_simple_for_source_item')->getItems()
        );
        $expectedData = [
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 100.0000,
            SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];
        $this->assertArrayHasKey(
            $sku,
            $compareData
        );
        $this->assertSame(
            $expectedData,
            $compareData[$sku]
        );
        $this->importedProducts = [$sku];
    }

    /**
     * Test that following a Product Import Source Item is updated as expected
     *
     * @return void
     */
    public function testSourceItemUpdatedOnProductImport(): void
    {
        $pathToFile = __DIR__ . '/_files/product_import_updated_qty.csv';
        /** @var Product $productImporterModel */
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();
        $sku = 'example_simple_for_source_item';
        $compareData = $this->buildDataArray(
            $this->getSourceItemList('example_simple_for_source_item')->getItems()
        );
        $expectedData = [
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 150.0000,
            SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];
        $this->assertArrayHasKey(
            $sku,
            $compareData
        );
        $this->assertSame(
            $expectedData,
            $compareData[$sku]
        );
        $this->importedProducts = [$sku];
    }

    /**
     * @magentoConfigFixture default/cataloginventory/options/synchronize_with_catalog 1
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryLowQuantityNotificationApi::Test/_files/source_item_configuration.php
     *
     * @return void
     */
    public function testSourceItemDeletedOnProductImport(): void
    {
        $this->objectManager->get(ClearQueueProcessor::class)->execute('inventory.source.items.cleanup');
        $pathToFile = __DIR__ . '/_files/product_import_SKU-1.csv';
        $productSku = 'SKU-1';
        $productImporterModel = $this->getProductImporterModel($pathToFile, Import::BEHAVIOR_DELETE);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();
        $this->importedProducts[] = $productSku;
        $this->processMessages();

        $this->assertEmpty($this->getSourceItemList($productSku)->getItems());
        $this->assertEmpty($this->getBySku->execute($productSku));
    }

    #[
        AppArea(\Magento\Framework\App\Area::AREA_ADMINHTML),
        AppIsolation(true), // needed for object manager config changes.
        DbIsolation(false),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(SourceFixture::class, as: 'source3'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source3.source_code$'],
            ]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            [
                'website_ids' => ['1', '$website2.id$'],
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => 3,
                        'is_in_stock' => true,
                        'min_qty' => 5,
                        'use_config_min_qty' => false,
                    ],
                ],
            ],
            'product'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$product.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 4],
                ['sku' => '$product.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 1],
            ]
        ),
        DataFixture(
            CsvFileFixture::class,
            [
                'rows' => [
                    ['sku', 'store_view_code', 'out_of_stock_qty'],
                    ['$product.sku$', '', '5'],
                ]
            ],
            'out_of_stock_qty_5_import_file'
        ),
        DataFixture(
            CsvFileFixture::class,
            [
                'rows' => [
                    ['sku', 'store_view_code', 'out_of_stock_qty'],
                    ['$product.sku$', '', '2'],
                ]
            ],
            'out_of_stock_qty_2_import_file'
        ),
    ]
    public function testShouldReindexCustomStockWhenOutOfStockThresholdConfigChanges(): void
    {
        // Important: GetLegacyStockItemsCache must be used to emulate legacy stock item loading from cache as
        // it is done outside test environment.
        $this->objectManager->configure([
            'preferences' => [
                GetLegacyStockItemsInterface::class => GetLegacyStockItemsCache::class,
            ]
        ]);

        $fixtures = DataFixtureStorageManager::getStorage();
        $getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
        $getProductSalableQty = Bootstrap::getObjectManager()->get(GetProductSalableQty::class);
        $sku = $fixtures->get('product')->getSku();
        $stock1 = Stock::DEFAULT_STOCK_ID;
        $stock2 = (int) $fixtures->get('stock2')->getId();

        // Current stock status when out_of_stock_qty = 5
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock1));
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock2));

        // Import out_of_stock_qty = 2
        $pathToFile = $fixtures->get('out_of_stock_qty_2_import_file')->getAbsolutePath();
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();

        $this->assertTrue((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(1, $getProductSalableQty->execute($sku, $stock1));
        $this->assertTrue((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(3, $getProductSalableQty->execute($sku, $stock2));

        // Import out_of_stock_qty = 5
        $pathToFile = $fixtures->get('out_of_stock_qty_5_import_file')->getAbsolutePath();
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();

        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock1));
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock2));
    }

    #[
        AppArea(\Magento\Framework\App\Area::AREA_ADMINHTML),
        AppIsolation(true), // needed for object manager config changes.
        DbIsolation(false),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(SourceFixture::class, as: 'source3'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source3.source_code$'],
            ]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            [
                'website_ids' => ['1', '$website2.id$'],
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => 0,
                        'is_in_stock' => true,
                    ],
                ],
            ],
            'product'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$product.sku$', 'source_code' => 'default', 'quantity' => 0],
                ['sku' => '$product.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 0],
                ['sku' => '$product.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 0],
            ]
        ),
        DataFixture(
            CsvFileFixture::class,
            [
                'rows' => [
                    ['sku', 'store_view_code', 'allow_backorders'],
                    ['$product.sku$', '', '1'],
                ]
            ],
            'backorder_enabled_import_file'
        ),
        DataFixture(
            CsvFileFixture::class,
            [
                'rows' => [
                    ['sku', 'store_view_code', 'allow_backorders'],
                    ['$product.sku$', '', '0'],
                ]
            ],
            'backorder_disabled_import_file'
        ),
    ]
    public function testShouldReindexCustomStockWhenBackordersConfigChanges(): void
    {
        // Important: GetLegacyStockItemsCache must be used to emulate legacy stock item loading from cache as
        // it is done outside test environment.
        $this->objectManager->configure([
            'preferences' => [
                GetLegacyStockItemsInterface::class => GetLegacyStockItemsCache::class,
            ]
        ]);
        $fixtures = DataFixtureStorageManager::getStorage();
        $getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
        $getProductSalableQty = Bootstrap::getObjectManager()->get(GetProductSalableQty::class);
        $sku = $fixtures->get('product')->getSku();
        $stock1 = Stock::DEFAULT_STOCK_ID;
        $stock2 = (int) $fixtures->get('stock2')->getId();

        // Current stock status when backorders = 0
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock1));
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock2));

        // Import backorders = 1
        $pathToFile = $fixtures->get('backorder_enabled_import_file')->getAbsolutePath();
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();

        $this->assertTrue((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock1));
        $this->assertTrue((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock2));

        // Import backorders = 0
        $pathToFile = $fixtures->get('backorder_disabled_import_file')->getAbsolutePath();
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporterModel->importData();

        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock1)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock1));
        $this->assertFalse((bool) $getStockItemData->execute($sku, $stock2)[GetStockItemData::IS_SALABLE]);
        $this->assertEquals(0, $getProductSalableQty->execute($sku, $stock2));
    }

    #[
        AppArea(\Magento\Framework\App\Area::AREA_ADMINHTML),
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple1']),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => 'simple1', 'source_code' => 'default', 'quantity' => 0],
                ['sku' => 'simple1', 'source_code' => 'source2', 'quantity' => 100],
            ]
        ),
        DataFixture(
            CsvFileFixture::class,
            [
                'rows' => [
                    ['product_type', 'sku', 'name', 'product_websites', 'associated_skus', 'attribute_set_code'],
                    ['grouped', 'grouped1', 'Grouped Product 1', 'base', 'simple1=5', 'Default'],
                ]
            ],
            'grouped_product_import_file'
        ),
    ]
    public function testShouldReindexCustomStockWhenCompositeProductImported(): void
    {
        $getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
        $fixtures = DataFixtureStorageManager::getStorage();
        $stockId = (int) $fixtures->get('stock2')->getId();

        $pathToFile = $fixtures->get('grouped_product_import_file')->getAbsolutePath();
        $productImporterModel = $this->getProductImporterModel($pathToFile);
        $errors = $productImporterModel->validateData();
        self::assertEquals(0, $errors->getErrorsCount());
        $productImporterModel->importData();

        $stockItemData = $getStockItemData->execute('grouped1', $stockId);
        self::assertNotEmpty($stockItemData);
        self::assertTrue((bool) $stockItemData[GetStockItemData::IS_SALABLE]);
    }

    /**
     * Process messages
     *
     * @return void
     */
    private function processMessages(): void
    {
        $consumer = $this->consumerFactory->get('inventory.source.items.cleanup');
        $consumer->process(1);
    }

    /**
     * Get List of Source Items which match SKU and Source ID of dummy data
     *
     * @param string $sku
     * @return SourceItemSearchResultsInterface
     */
    private function getSourceItemList(string $sku): SourceItemSearchResultsInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteria */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaBuilder->addFilter(
            SourceItemInterface::SKU,
            $sku
        );

        $searchCriteriaBuilder->addFilter(
            SourceItemInterface::SOURCE_CODE,
            $this->defaultSourceProvider->getCode()
        );

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->sourceItemRepository->getList($searchCriteria);
    }

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function buildDataArray(array $sourceItems): array
    {
        $comparableArray = [];
        foreach ($sourceItems as $sourceItem) {
            $comparableArray[$sourceItem->getSku()] = [
                SourceItemInterface::SKU => $sourceItem->getSku(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        return $comparableArray;
    }

    /**
     * Return Product Importer Model for use with tests requires path to CSV import file
     *
     * @param string $pathToFile
     * @param string $behavior See Magento\ImportExport\Model\Source\Import\Behavior\Basic::toArray for proper mapping
     * @return Product
     */
    private function getProductImporterModel(
        string $pathToFile,
        string $behavior = Import::BEHAVIOR_APPEND
    ): Product {
        /** @var Filesystem\Directory\WriteInterface $directory */
        $directory = $this->filesystem
            ->getDirectoryWrite(DirectoryList::ROOT);
        /** @var Csv $source */
        $source = $this->objectManager->get(CsvFactory::class)->create(
            [
                'file' => $pathToFile,
                'directory' => $directory
            ]
        );
        $productImporter = $this->productImporterFactory->create();
        $productImporter->setParameters(
            [
                'behavior' => $behavior,
                'entity' => \Magento\Catalog\Model\Product::ENTITY
            ]
        )->setSource($source);

        return $productImporter;
    }

    /**
     * Cleanup test by removing products.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        if (!empty($this->importedProducts)) {
            /** @var ProductRepositoryInterface $productRepository */
            $productRepository = $objectManager->create(ProductRepositoryInterface::class);
            $registry = $objectManager->get(\Magento\Framework\Registry::class);
            /** @var ProductRepositoryInterface $productRepository */
            $registry->unregister('isSecureArea');
            $registry->register('isSecureArea', true);

            foreach ($this->importedProducts as $sku) {
                try {
                    $productRepository->deleteById($sku);
                } catch (NoSuchEntityException $e) {
                    // product already deleted
                }
            }

            $registry->unregister('isSecureArea');
            $registry->register('isSecureArea', false);
        }
        /** @var Data $dataSourceModel */
        $dataSourceModel = $objectManager->create(Data::class);
        $dataSourceModel->cleanBunches();
    }
}
