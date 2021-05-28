<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProductIndexer\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryGroupedProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\ProductRepositoryInterface;

class SourceItemIndexerTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceItemsSave = $objectManager->get(SourceItemsSaveInterface::class);
        $this->getStockItemData = $objectManager->get(GetStockItemDataInterface::class);
        $this->sourceItemIndexer = $objectManager->get(SourceItemIndexer::class);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_with_eu_website_grouped_products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/source_items_grouped_multiple.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testOneSimpleChangesToOutOfStockInOneSource()
    {
        $groupedSku = 'grouped_in_stock';
        $this->changeStockStatusForSku('simple_11', 'eu-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_11', 'us-1', SourceItemInterface::STATUS_OUT_OF_STOCK);

        // EU-Stock
        $data = $this->getStockItemData->execute($groupedSku, 10);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);

        // US-Stock
        $data = $this->getStockItemData->execute($groupedSku, 20);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);

        // Global-Stock
        $data = $this->getStockItemData->execute($groupedSku, 30);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_with_eu_website_grouped_products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/source_items_grouped_multiple.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testAllSimplesChangesToOutOfStockInOneSource()
    {
        $this->changeStockStatusForSku('simple_11', 'eu-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_11', 'us-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_22', 'us-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $groupedSku = 'grouped_in_stock';

        // EU-Stock
        $data = $this->getStockItemData->execute($groupedSku, 10);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);

        // US-Stock
        $data = $this->getStockItemData->execute($groupedSku, 20);
        $this->assertEquals(0, $data[GetStockItemDataInterface::IS_SALABLE]);

        // Global-Stock
        $data = $this->getStockItemData->execute($groupedSku, 30);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_with_eu_website_grouped_products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/source_items_grouped_multiple.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testAllSimplesChangesToOutOfStockInAllSources()
    {
        $this->changeStockStatusForSku('simple_11', 'us-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_22', 'us-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-1', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-2', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-2', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-3', SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-3', SourceItemInterface::STATUS_OUT_OF_STOCK);

        $groupedSku = 'grouped_in_stock';
        // EU-Stock
        $data = $this->getStockItemData->execute($groupedSku, 10);
        $this->assertEquals(0, $data[GetStockItemDataInterface::IS_SALABLE]);

        // US-Stock
        $data = $this->getStockItemData->execute($groupedSku, 20);
        $this->assertEquals(0, $data[GetStockItemDataInterface::IS_SALABLE]);

        // Global-Stock
        $data = $this->getStockItemData->execute($groupedSku, 30);
        $this->assertEquals(0, $data[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_with_eu_website_grouped_products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/source_items_grouped_multiple.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testAllSimplesChangesToInStock()
    {
        $this->changeStockStatusForSku('simple_11', 'us-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_22', 'us-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-1', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-2', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-2', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_11', 'eu-3', SourceItemInterface::STATUS_IN_STOCK);
        $this->changeStockStatusForSku('simple_22', 'eu-3', SourceItemInterface::STATUS_IN_STOCK);

        $groupedSku = 'grouped_in_stock';
        // EU-Stock
        $data = $this->getStockItemData->execute($groupedSku, 10);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);

        // US-Stock
        $data = $this->getStockItemData->execute($groupedSku, 20);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);

        // Global-Stock
        $data = $this->getStockItemData->execute($groupedSku, 30);
        $this->assertEquals(1, $data[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_with_source_link.php
     * @magentoDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_grouped_products.php
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteListWithDisabledSimple(): void
    {
        $items = $this->getSourceItems('simple_11', 'source-code-1');
        $itemId = array_keys($items)[0];
        $this->sourceItemIndexer->executeList([$itemId]);

        $grouped1StockData = $this->getStockItemData->execute('grouped_1', 10);
        $grouped2StockData = $this->getStockItemData->execute('grouped_2', 10);

        $this->assertEquals(1, $grouped1StockData[GetStockItemDataInterface::IS_SALABLE]);
        $this->assertEquals(1, $grouped2StockData[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param int $stockStatus
     */
    private function changeStockStatusForSku(string $sku, string $sourceCode, int $stockStatus)
    {
        $sourceItems = $this->getSourceItems($sku, $sourceCode);
        foreach ($sourceItems as $sourceItem) {
            $sourceItem->setStatus($stockStatus);
        }

        $this->sourceItemsSave->execute($sourceItems);
    }

    /**
     * Load source items
     *
     * @param string $sku
     * @param string $sourceCode
     * @return SourceItemInterface[]
     */
    private function getSourceItems(string $sku, string $sourceCode): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();
        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }
}
