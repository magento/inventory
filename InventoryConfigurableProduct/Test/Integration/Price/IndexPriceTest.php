<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Price;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\DB\Select;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface as StoreScope;
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @var Processor
     */
    private $indexerProcessor;

    /**
     * @var MutableScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();
        $this->indexerProcessor = $this->objectManager->create(Processor::class);
        $this->scopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
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
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/set_product_configurable_out_of_stock_all.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 1
     * @return void
     */
    public function testIndexPriceWhenOutOfStockInDefaultStock(): void
    {
        $this->updateConfigShowOutOfStockFlag(1);
        $this->indexerProcessor->reindexAll();

        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        /** @var Select $select */
        $select = $resource->getConnection()->select();
        $select->from(
            ['price_index' => $resource->getTableName('catalog_product_index_price')],
            ['entity_id', 'min_price', 'max_price']
        );
        $select->where("price_index.entity_id IN (?)", 1);
        $select->where('price_index.min_price = ?', 10);
        $select->where('price_index.max_price = ?', 20);
        $result = $select->query()->fetchAll();

        self::assertNotEquals(0, count($result));
    }

    /**
     * Updates store config 'cataloginventory/options/show_out_of_stock' flag.
     *
     * @param int $showOutOfStock
     * @return void
     */
    private function updateConfigShowOutOfStockFlag(int $showOutOfStock): void
    {
        $this->scopeConfig->setValue(
            Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
            $showOutOfStock,
            StoreScope::SCOPE_STORE,
            ScopeInterface::SCOPE_DEFAULT
        );
    }
}
