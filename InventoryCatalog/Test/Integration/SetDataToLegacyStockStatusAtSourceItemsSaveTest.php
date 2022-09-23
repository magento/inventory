<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
}
