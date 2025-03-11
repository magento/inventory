<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\CatalogInventory\Model\ResourceModel\Stock\Status;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatus;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test catalog search with different stocks
 */
class AddStockDataToCollectionTest extends TestCase
{
    /**
     * @var StockStatus
     */
    private $stockStatus;

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

        $this->stockStatus = Bootstrap::getObjectManager()->get(StockStatus::class);
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
     *
     * @param string $store
     * @param int $expectedSize
     * @param bool $isFilterInStock
     * @return void
     *
     * @dataProvider addStockDataToCollectionDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testAddStockDataToCollection(string $store, int $expectedSize, bool $isFilterInStock)
    {
        $this->storeManager->setCurrentStore($store);

        /** @var Collection $collection */
        $collection = Bootstrap::getObjectManager()->create(Collection::class);
        $this->stockStatus->addStockDataToCollection($collection, $isFilterInStock);

        self::assertEquals($expectedSize, $collection->getSize());
    }

    /**
     * @return array
     */
    public static function addStockDataToCollectionDataProvider(): array
    {
        return [
            ['store_for_eu_website', 3, true],
            ['store_for_us_website', 1, true],
            ['store_for_global_website', 4, true],
            // all products including out of stock and those not assigned website stock
            ['store_for_eu_website', 6, false],
            ['store_for_us_website', 6, false],
            ['store_for_global_website', 6, false],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (null !== $this->storeCodeBefore) {
            $this->storeManager->setCurrentStore($this->storeCodeBefore);
        }
        parent::tearDown();
    }
}
