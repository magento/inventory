<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Price;

use Magento\Customer\Model\Group;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for index price configurable.
 */
class IndexPriceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @var Processor
     */
    private $indexerProcessor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();
        $this->indexerProcessor = $this->objectManager->create(Processor::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (null !== $this->storeCodeBefore) {
            $this->storeManager->setCurrentStore($this->storeCodeBefore);
        }
    }

    /**
     * Test index price when out of stock in default stock
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_option_1_out_of_stock_default.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_option_2_out_of_stock_default.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store cataloginventory/options/show_out_of_stock 1
     * @return void
     */
    public function testIndexPriceWhenOutOfStockInDefaultStock(): void
    {
        $this->indexerProcessor->reindexAll();
        $website = $this->storeManager->getWebsite('base');
        $result = $this->getMinAndMaxPrice(1, (int) $website->getId(), Group::NOT_LOGGED_IN_ID);

        self::assertIsArray($result);
        self::assertEquals(10, $result['min_price']);
        self::assertEquals(20, $result['max_price']);
    }

    /**
     * Test index price when out of stock in custom stock
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_product_configurable_out_of_stock_all.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store cataloginventory/options/show_out_of_stock 1
     * @return void
     */
    public function testIndexPriceWhenOutOfStockInCustomStock(): void
    {
        $this->indexerProcessor->reindexAll();
        $website = $this->storeManager->getWebsite('us_website');
        $result = $this->getMinAndMaxPrice(1, (int) $website->getId(), Group::NOT_LOGGED_IN_ID);
        self::assertIsArray($result);
        self::assertEquals(10, $result['min_price']);
        self::assertEquals(20, $result['max_price']);
    }

    /**
     * Test index price when some options are in stock and disabled and the other options are out-stock
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_product_configurable_out_of_stock.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/disable_option_2.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store cataloginventory/options/show_out_of_stock 1
     * @return void
     */
    public function testIndexPriceWhenOneChildOutOfStockAndOtherDisabledInStock(): void
    {
        $this->indexerProcessor->reindexAll();
        $website = $this->storeManager->getWebsite('us_website');
        $result = $this->getMinAndMaxPrice(1, (int) $website->getId(), Group::NOT_LOGGED_IN_ID);
        self::assertIsArray($result);
        self::assertEquals(10, $result['min_price']);
        self::assertEquals(10, $result['max_price']);
    }

    /**
     * @param int $entityId
     * @param int $websiteId
     * @param int $customerGroupId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getMinAndMaxPrice(int $entityId, int $websiteId, int $customerGroupId)
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $select = $resource->getConnection()->select();
        $select->from(
            ['price_index' => $resource->getTableName('catalog_product_index_price')],
            ['entity_id', 'min_price', 'max_price']
        );
        $select->where("price_index.website_id = ?", $websiteId);
        $select->where("price_index.customer_group_id = ?", $customerGroupId);
        $select->where("price_index.entity_id = ?", $entityId);
        return $resource->getConnection()->fetchRow($select);
    }
}
