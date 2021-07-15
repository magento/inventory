<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Test\Integration\Model\Indexer;

use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogSearch\Model\Indexer\Fulltext as CatalogSearchIndexer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\DeploymentConfig\FileReader;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Indexer\Model\Indexer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class FulltextIndexerBatchTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Indexer|mixed
     */
    private $indexer;

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
     * @throws FileSystemException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->configFilePool = $this->objectManager->get(ConfigFilePool::class);
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->reader = $this->objectManager->get(FileReader::class);
        $this->writer = $this->objectManager->get(Writer::class);
        $this->sourceItemsSave = $this->objectManager->get(SourceItemsSaveInterface::class);
        $this->sourceItemFactory = $this->objectManager->get(SourceItemInterfaceFactory::class);
        $this->fulltextCollection = $this->objectManager->create(SearchLayer::class)->getProductCollection();
        $this->indexer = $this->objectManager->get(Indexer::class);

        // In order to trigger the issue without creating >500 products (default batch size),
        // we must reduce the indexer batch size to 1 in the eav.conf.
        // We temporary save the previous configuration and then restore it at the tearDown.
        $this->envConfig = $this->reader->load(ConfigFilePool::APP_ENV);
        $updatedEnvConfig = $this->envConfig;
        $updatedEnvConfig['indexer'] = [
            'batch_size' => [
                'catalogsearch_fulltext' => [
                    'mysql_get' => 1
                ]
            ]
        ];
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $updatedEnvConfig]);

        $this->indexer->load(CatalogSearchIndexer::INDEXER_ID);
    }

    /**
     * Test fulltext reindex in case when first batch will only contain salable products,
     * second batch will only contain non-salable products, the rest of the batches should
     * be correctly indexed and not ignored
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/change_stock_for_base_website.php
     *
     * @magentoDbIsolation disabled
     * @magentoAppArea frontend
     * @throws \Throwable
     */
    public function testReindexWithEntireBatchOutOfStock()
    {
        $customStockSourceCode = 'source-code-1';
        $itemsData = [
            'SKU-1' => 10,
            'SKU-2' => 0,
            'SKU-3' => 10,
            'SKU-4' => 10,
            'SKU-5' => 10,
            'SKU-6' => 10
        ];
        $this->createSourceItems($customStockSourceCode, $itemsData);

        $this->indexer->reindexAll();

        $this->fulltextCollection->addSearchFilter('SKU');
        $items = $this->fulltextCollection->getItems();

        $this->assertCount(5, $items);

        $this->deleteSourceItems($itemsData);
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
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        // Restore the EAV Config to the previously saved value
        $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG)->writeFile(
            $this->configFilePool->getPath(ConfigFilePool::APP_ENV),
            "<?php\n return array();\n"
        );
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $this->envConfig]);

        parent::tearDown();
    }
}
