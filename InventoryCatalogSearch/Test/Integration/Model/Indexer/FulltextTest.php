<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Test\Integration\Model\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\DeploymentConfig\FileReader;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Indexer\Model\Indexer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\CatalogSearch\Model\Indexer\Fulltext as CatalogSearchIndexer;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\FileSystemException;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;

/**
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class FulltextTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var Collection
     */
    private $fulltextCollection;

    /**
     * @var ConfigFilePool
     */
    private $configFilePool;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FileReader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var array
     */
    private $envConfig;


    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->queryFactory = $this->objectManager->get(QueryFactory::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->indexer = $this->objectManager->get(Indexer::class);
        $this->indexer->load(CatalogSearchIndexer::INDEXER_ID);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();

        $this->configFilePool = $this->objectManager->get(ConfigFilePool::class);
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->reader = $this->objectManager->get(FileReader::class);
        $this->writer = $this->objectManager->get(Writer::class);
        $this->sourceItemsSave = $this->objectManager->get(SourceItemsSaveInterface::class);
        $this->sourceItemFactory = $this->objectManager->get(SourceItemInterfaceFactory::class);
        $this->fulltextCollection = $this->objectManager->create(SearchLayer::class)->getProductCollection();

        // Save the current sate of env config for future rollback
        $this->envConfig = $this->reader->load(ConfigFilePool::APP_ENV);
    }

    /**
     * Test fulltext reindex in case when first batch will only contain salable products,
     * second batch will only contain non-salable products, the rest of the batches should
     * be correctly indexed and not ignored
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/change_stock_for_base_website.php
     *
     * @magentoAppArea frontend
     * @throws \Throwable
     */
    public function testReindexWithEntireBatchOutOfStock()
    {
        // In order to trigger the issue without creating >500 products (default batch size),
        // we must reduce the indexer batch size to 1 in the eav.conf.
        // We temporary save the previous configuration and then restore it at the tearDown.
        $updatedEnvConfig = $this->envConfig;
        $updatedEnvConfig['indexer'] = [
            'batch_size' => [
                'catalogsearch_fulltext' => [
                    'mysql_get' => 1
                ]
            ]
        ];
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $updatedEnvConfig]);

        $customStockSourceCode = 'source-code-1';
        $itemsData = [
            'SKU-1' => 10,
            'SKU-2' => 0,
            'SKU-3' => 10,
            'SKU-4' => 10,
            'SKU-5' => 10,
            'SKU-6' => 10,
        ];
        $this->createSourceItems($customStockSourceCode, $itemsData);

        $this->indexer->reindexAll();

        $this->fulltextCollection->addSearchFilter('SKU');
        $items = $this->fulltextCollection->getItems();

        $this->deleteSourceItems($itemsData);
        $this->assertCount(5, $items);
    }

    /**
     * @param string $queryText
     * @param string $store
     * @param int $expectedSize
     * @return void
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/assign_products_to_websites.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @dataProvider searchPerStockDataProvider
     * @throws LocalizedException
     */
    public function testSearchPerStock(string $queryText, string $store, int $expectedSize)
    {
        $this->storeManager->setCurrentStore($store);
        $this->indexer->reindexAll();

        $products = $this->search($queryText);

        $this->assertCount($expectedSize, $products);
    }

    /**
     * @return array
     */
    public function searchPerStockDataProvider(): array
    {
        return [
            ['Orange', 'store_for_eu_website', 1],
            ['Orange', 'store_for_us_website', 0],
            ['Orange', 'store_for_global_website', 1],

            ['Blue', 'store_for_eu_website', 0],
            ['Blue', 'store_for_us_website', 1],
            ['Blue', 'store_for_global_website', 1],

            ['White', 'store_for_eu_website', 0],
            ['White', 'store_for_us_website', 0],
            ['White', 'store_for_global_website', 0],
        ];
    }

    /**
     * Search the text and return result collection.
     *
     * @param string $text
     * @return ProductInterface[]
     * @throws LocalizedException
     */
    private function search(string $text): array
    {
        $query = $this->queryFactory->create();
        $query->setQueryText($text);
        $query->saveIncrementalPopularity();

        /** @var \Magento\Catalog\Model\Layer\Search $layer */
        $layer = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Layer\Search::class);
        $collection = $layer->getProductCollection();

        $products = $collection
            ->addSearchFilter($text)
            ->getItems();
        return $products;
    }

    /**
     * @param array $items
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function deleteSourceItems(array $items)
    {
        /** @var SourceItemRepositoryInterface $sourceItemRepository */
        $sourceItemRepository = $this->objectManager->get(SourceItemRepositoryInterface::class);

        /** @var SourceItemsDeleteInterface $sourceItemsDelete */
        $sourceItemsDelete = $this->objectManager->get(SourceItemsDeleteInterface::class);

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);

        $itemsSKUs = array_keys($items);
        $searchCriteria = $searchCriteriaBuilder->addFilter(
            SourceItemInterface::SKU,
            $itemsSKUs,
            'in'
        )->create();

        $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();

        if (!empty($sourceItems)) {
            $sourceItemsDelete->execute($sourceItems);
        }
    }

    /**
     * @param string $sourceCode
     * @param array $itemsData
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    private function createSourceItems(
        string $sourceCode,
        array $itemsData
    ) {
        foreach ($itemsData as $sku => $qty) {
            $sourceItemParams = [
                'data' => [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $sku,
                    SourceItemInterface::QUANTITY => $qty,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK
                ]
            ];
            $sourceItem = $this->sourceItemFactory->create($sourceItemParams);
            $this->sourceItemsSave->execute([$sourceItem]);
        }
    }

    /**
     * @inheritdoc
     * @throws FileSystemException
     */
    protected function tearDown(): void
    {
        if (null !== $this->storeCodeBefore) {
            $this->storeManager->setCurrentStore($this->storeCodeBefore);
        }

        // Restore the EAV Config to the previously saved value
        $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG)->writeFile(
            $this->configFilePool->getPath(ConfigFilePool::APP_ENV),
            "<?php\n return array();\n"
        );
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $this->envConfig]);

        parent::tearDown();
    }
}
