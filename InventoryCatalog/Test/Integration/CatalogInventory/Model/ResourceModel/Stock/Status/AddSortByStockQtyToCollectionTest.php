<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\CatalogInventory\Model\ResourceModel\Stock\Status;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Select;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test sort stock quantity for custom stock
 */
class AddSortByStockQtyToCollectionTest extends TestCase
{
    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @param string $storeCode
     * @param string $dir
     * @param array $expectedOrder
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(string $storeCode, string $dir, array $expectedOrder): void
    {
        /** @var Collection $collection */
        $collection = Bootstrap::getObjectManager()->create(Collection::class);
        $collection->setStore($storeCode);
        $collection->addAttributeToSort('quantity_and_stock_status', $dir);
        $collection->addAttributeToSort('entity_id', Select::SQL_DESC);

        self::assertEquals($expectedOrder, $collection->getColumnValues('sku'));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'store_for_eu_website',
                Select::SQL_DESC,
                ['SKU-1', 'SKU-4', 'SKU-6', 'SKU-3', 'SKU-5', 'SKU-2']
            ],
            [
                'store_for_eu_website',
                Select::SQL_ASC,
                ['SKU-5', 'SKU-2', 'SKU-6', 'SKU-3', 'SKU-4', 'SKU-1']
            ],
            [
                'store_for_us_website',
                Select::SQL_DESC,
                ['SKU-2', 'SKU-6', 'SKU-5', 'SKU-4', 'SKU-3', 'SKU-1']
            ],
            [
                'store_for_us_website',
                Select::SQL_ASC,
                ['SKU-6', 'SKU-5', 'SKU-4', 'SKU-3', 'SKU-1', 'SKU-2']
            ],
            [
                'store_for_global_website',
                Select::SQL_DESC,
                ['SKU-1', 'SKU-4', 'SKU-2', 'SKU-6', 'SKU-3', 'SKU-5']
            ],
            [
                'store_for_global_website',
                Select::SQL_ASC,
                ['SKU-5', 'SKU-6', 'SKU-3', 'SKU-2', 'SKU-4', 'SKU-1']
            ]
        ];
    }
}
