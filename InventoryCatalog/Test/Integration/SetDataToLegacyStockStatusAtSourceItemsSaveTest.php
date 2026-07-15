<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Model\Stock\Status;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Model\IsProductSalable;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;

/**
 * Tests legacy stock information synchronized with MSI's.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetDataToLegacyStockStatusAtSourceItemsSaveTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockStatusCriteriaInterfaceFactory
     */
    private $legacyStockStatusCriteriaFactory;

    /**
     * @var StockStatusRepositoryInterface
     */
    private $legacyStockStatusRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);

        $this->legacyStockStatusCriteriaFactory = Bootstrap::getObjectManager()->get(
            StockStatusCriteriaInterfaceFactory::class
        );
        $this->legacyStockStatusRepository = Bootstrap::getObjectManager()->get(StockStatusRepositoryInterface::class);

        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);

        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->defaultSourceProvider = Bootstrap::getObjectManager()->get(DefaultSourceProviderInterface::class);

        $this->indexerRegistry = Bootstrap::getObjectManager()
            ->get(IndexerRegistry::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalable::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
    }

    /**
     * Tests that legacy stock status data will updates with legacy source item data while the indexer is "On Update".
     *
     * @return void
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testSetDataIndexedOnUpdate(): void
    {
        $productSku = 'SKU-1';
        $product = $this->productRepository->get($productSku);
        $productId = $product->getId();
        $websiteId = 0;

        /** @var StockStatusCriteriaInterface $legacyStockStatusCriteria */
        $legacyStockStatusCriteria = $this->legacyStockStatusCriteriaFactory->create();
        $legacyStockStatusCriteria->setProductsFilter($productId);
        $legacyStockStatusCriteria->setScopeFilter($websiteId);
        $legacyStockStatuses = $this->legacyStockStatusRepository->getList($legacyStockStatusCriteria)->getItems();
        self::assertCount(1, $legacyStockStatuses);

        $legacyStockStatus = reset($legacyStockStatuses);
        self::assertEquals(Status::STATUS_IN_STOCK, $legacyStockStatus->getStockStatus());
        self::assertEquals(5.5, $legacyStockStatus->getQty());

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $productSku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode())
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        self::assertCount(1, $sourceItems);

        $sourceItem = reset($sourceItems);
        $sourceItem->setQuantity(20);
        $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->sourceItemsSave->execute($sourceItems);

        $legacyStockStatuses = $this->legacyStockStatusRepository->getList($legacyStockStatusCriteria)->getItems();
        self::assertCount(1, $legacyStockStatuses);

        $legacyStockStatus = current($legacyStockStatuses);
        self::assertEquals(Status::STATUS_OUT_OF_STOCK, $legacyStockStatus->getStockStatus());
        self::assertEquals(20, $legacyStockStatus->getQty());
    }

    /**
     * Tests that legacy stock status data will updates with legacy source item data with scheduled indexer.
     * Should work like with indexing "on update".
     *
     * @return void
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     **/
    public function testSetDataWithoutIndexing(): void
    {
        $indexer = $this->indexerRegistry->get(Processor::INDEXER_ID);
        $indexer->setScheduled(true);

        $this->testSetDataIndexedOnUpdate();

        $indexer->setScheduled(false);
    }

    #[
        DataFixture(Product::class, ['stock_item' => ['qty' => 0]], 'product')
    ]
    public function testProductWithQtyZeroShouldBeOutOfStock(): void
    {
        $product = $this->fixtures->get('product');
        $this->assertFalse($this->isProductSalable->execute($product->getSku(), Stock::DEFAULT_STOCK_ID));
    }

    /**
     * The frontend area routes IsProductSalableInterface/AreProductsSalableInterface to a
     * read-optimized index reader that selects straight from cataloginventory_stock_status - the
     * very row this synchronization is about to write. Legacy stock status must still reflect the
     * newly saved source item, not the pre-write row, in that area.
     *
     * @return void
     * @magentoAppArea frontend
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testStatusUpdatedAfterSourceItemsSaveInFrontendArea(): void
    {
        $productSku = 'SKU-4';
        // SKU-4 starts at quantity 0 / out of stock (source_items_on_default_source.php fixture).
        self::assertFalse($this->isProductSalable->execute($productSku, Stock::DEFAULT_STOCK_ID));

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $productSku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode())
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        self::assertCount(1, $sourceItems);

        $sourceItem = reset($sourceItems);
        $sourceItem->setQuantity(10);
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItemsSave->execute([$sourceItem]);

        $product = $this->productRepository->get($productSku);
        $legacyStockStatusCriteria = $this->legacyStockStatusCriteriaFactory->create();
        $legacyStockStatusCriteria->setProductsFilter($product->getId());
        $legacyStockStatusCriteria->setScopeFilter(0);
        $legacyStockStatuses = $this->legacyStockStatusRepository->getList($legacyStockStatusCriteria)->getItems();
        self::assertCount(1, $legacyStockStatuses);

        $legacyStockStatus = reset($legacyStockStatuses);
        self::assertEquals(Status::STATUS_IN_STOCK, $legacyStockStatus->getStockStatus());
        self::assertEquals(10, $legacyStockStatus->getQty());
        self::assertTrue($this->isProductSalable->execute($productSku, Stock::DEFAULT_STOCK_ID));
    }

    /**
     * Same as testStatusUpdatedAfterSourceItemsSaveInFrontendArea, with the stock indexer in
     * "Update by Schedule" mode. Should behave identically since the legacy stock status write in
     * UpdateDefaultStock does not depend on the indexer having already run.
     *
     * @return void
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testStatusUpdatedAfterSourceItemsSaveInFrontendAreaWithScheduledIndexer(): void
    {
        $indexer = $this->indexerRegistry->get(Processor::INDEXER_ID);
        $indexer->setScheduled(true);

        $this->testStatusUpdatedAfterSourceItemsSaveInFrontendArea();

        $indexer->setScheduled(false);
    }

    /**
     * Regression guard: re-saving a source item's own data unchanged must not clear a
     * reservation-driven "out of stock" status just because the source item's own status flag
     * says "in stock" on its own. The write must still go through the reservation-aware
     * condition chain rather than the legacy stock item flag alone.
     *
     * @return void
     * @magentoAppArea frontend
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testReservationDrivenOutOfStockSurvivesUnchangedSourceItemSaveInFrontendArea(): void
    {
        $productSku = 'SKU-1';
        // SKU-1 starts at quantity 5.5 / in stock (source_items_on_default_source.php fixture).
        self::assertTrue($this->isProductSalable->execute($productSku, Stock::DEFAULT_STOCK_ID));

        $this->appendReservations->execute([
            $this->reservationBuilder->setStockId(Stock::DEFAULT_STOCK_ID)
                ->setSku($productSku)
                ->setQuantity(-5.5)
                ->build(),
        ]);
        self::assertFalse($this->isProductSalable->execute($productSku, Stock::DEFAULT_STOCK_ID));

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $productSku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode())
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        // Re-save the source item unchanged; it still reports quantity 5.5 / in stock on its own.
        $this->sourceItemsSave->execute($sourceItems);

        $product = $this->productRepository->get($productSku);
        $legacyStockStatusCriteria = $this->legacyStockStatusCriteriaFactory->create();
        $legacyStockStatusCriteria->setProductsFilter($product->getId());
        $legacyStockStatusCriteria->setScopeFilter(0);
        $legacyStockStatuses = $this->legacyStockStatusRepository->getList($legacyStockStatusCriteria)->getItems();
        $legacyStockStatus = reset($legacyStockStatuses);

        self::assertEquals(
            Status::STATUS_OUT_OF_STOCK,
            $legacyStockStatus->getStockStatus(),
            'A fully-reserved product must stay out of stock after re-saving its source item unchanged.'
        );

        $this->cleanupReservations->execute();
    }
}
