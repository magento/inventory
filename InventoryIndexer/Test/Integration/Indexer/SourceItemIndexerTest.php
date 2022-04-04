<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Indexer;

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexProcessor;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceItemIndexerTest extends TestCase
{
    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var RemoveIndexData
     */
    private $removeIndexData;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $this->sourceItemIndexer = Bootstrap::getObjectManager()->get(SourceItemIndexer::class);
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
        $this->getSourceItemIds = Bootstrap::getObjectManager()->get(GetSourceItemIds::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->removeIndexData = Bootstrap::getObjectManager()->get(RemoveIndexData::class);
        $this->removeIndexData->execute([10, 20, 30]);
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown(): void
    {
        $this->removeIndexData->execute([10, 20, 30]);
    }

    /**
     * Source 'eu-1' is assigned on EU-stock(id:10) and Global-stock(id:30)
     * Thus these stocks stocks be reindexed only for SKU-1
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     *
     * @dataProvider reindexRowDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testReindexRow(string $sku, int $stockId, $expectedData)
    {
        $sourceItem = $this->getSourceItem('SKU-1', 'eu-1');
        $sourceItemIds = $this->getSourceItemIds->execute([$sourceItem]);
        foreach ($sourceItemIds as $sourceItemId) {
            $this->sourceItemIndexer->executeRow((int)$sourceItemId);
        }

        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * @return array
     */
    public function reindexRowDataProvider(): array
    {
        return [
            ['SKU-1', 10, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-1', 30, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, null],
            ['SKU-2', 30, null],
            ['SKU-3', 10, null],
            ['SKU-3', 30, null],
        ];
    }

    /**
     * Source 'eu-1' and 'us-1' are assigned on EU-stock(id:10), US-stock(id:20) and Global-stock(id:30)
     * Thus these stocks should be reindexed only for SKU-1 and for SKU-2
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     *
     * @dataProvider reindexListDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testReindexList(string $sku, int $stockId, $expectedData)
    {
        $sourceItemIds = $this->getSourceItemIds->execute(
            [
                $this->getSourceItem('SKU-1', 'eu-1'),
                $this->getSourceItem('SKU-2', 'us-1'),
            ]
        );
        $this->sourceItemIndexer->executeList($sourceItemIds);

        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * @return array
     */
    public function reindexListDataProvider(): array
    {
        return [
            ['SKU-1', 10, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-1', 20, null],
            ['SKU-1', 30, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, null],
            ['SKU-2', 20, [GetStockItemDataInterface::QUANTITY => 5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 30, [GetStockItemDataInterface::QUANTITY => 5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-3', 10, null],
            ['SKU-3', 20, null],
            ['SKU-3', 30, null],
        ];
    }

    /**
     * All of stocks should be reindexed for all of skus
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     *
     * @dataProvider reindexAllDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testReindexAll(string $sku, int $stockId, $expectedData)
    {
        $this->sourceItemIndexer->executeFull();

        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * @return array
     */
    public function reindexAllDataProvider(): array
    {
        return [
            ['SKU-1', 10, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-1', 20, null],
            ['SKU-1', 30, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, null],
            ['SKU-2', 20, [GetStockItemDataInterface::QUANTITY => 5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 30, [GetStockItemDataInterface::QUANTITY => 5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-3', 10, [GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0]],
            ['SKU-3', 20, null],
            ['SKU-3', 30, [GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0]],
        ];
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/assign_products_to_websites.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testShouldTriggerPriceReindex(): void
    {
        $sku = 'SKU-2';
        $sourceCode = 'us-1';
        $websiteCode = 'us_website';
        $objectManager = Bootstrap::getObjectManager();
        /** @var $sourceItemsSave SourceItemsSaveInterface */
        $sourceItemsSave = $objectManager->get(SourceItemsSaveInterface::class);
        $this->assertNotEmpty($this->getPriceIndexData($sku, $websiteCode));
        $sourceItem = $this->getSourceItem($sku, $sourceCode);
        $sourceItem->setQuantity(0);
        $sourceItemsSave->execute([$sourceItem]);

        $this->assertEmpty($this->getPriceIndexData($sku, $websiteCode));
        $sourceItem->setQuantity(1);
        $sourceItemsSave->execute([$sourceItem]);
        $this->assertNotEmpty($this->getPriceIndexData($sku, $websiteCode));
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/assign_products_to_websites.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture setPriceIndexerModeToScheduled
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testShouldTriggerPriceReindexRegardlessOfIndexMode(): void
    {
        $this->testShouldTriggerPriceReindex();
    }

    public static function setPriceIndexerModeToScheduled(): void
    {
        /** @var $priceIndexProcessor PriceIndexProcessor */
        $priceIndexProcessor = Bootstrap::getObjectManager()->get(PriceIndexProcessor::class);
        $priceIndexProcessor->getIndexer()->setScheduled(true);
    }

    public static function setPriceIndexerModeToScheduledRollback(): void
    {
        /** @var $priceIndexProcessor PriceIndexProcessor */
        $priceIndexProcessor = Bootstrap::getObjectManager()->get(PriceIndexProcessor::class);
        $priceIndexProcessor->getIndexer()->setScheduled(false);
    }

    /**
     * @param string $sku
     * @param string $websiteCode
     * @return array
     */
    private function getPriceIndexData(string $sku, string $websiteCode): array
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ResourceConnection $resource */
        $resource = $objectManager->get(ResourceConnection::class);
        $storeManager = $objectManager->get(StoreManagerInterface::class);
        $website = $storeManager->getWebsite($websiteCode);
        $select = $resource->getConnection()
            ->select()
            ->from(
                ['e' => $resource->getTableName('catalog_product_entity')],
                []
            )
            ->join(
                ['price_index' => $resource->getTableName('catalog_product_index_price')],
                'e.entity_id = price_index.entity_id',
            )
            ->where('e.sku = ?', $sku)
            ->where('price_index.website_id = ?', $website->getId());

        return $resource->getConnection()->fetchall($select) ?? [];
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @return SourceItemInterface
     */
    private function getSourceItem(string $sku, string $sourceCode): SourceItemInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        return reset($sourceItems);
    }
}
