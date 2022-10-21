<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\GetStockItemData;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BackorderConditionTest extends TestCase
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemDataInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $this->stockItemCriteriaFactory = Bootstrap::getObjectManager()->get(
            StockItemCriteriaInterfaceFactory::class
        );
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $resourceConnection->getConnection()
            ->delete(
                $resourceConnection->getTableName('inventory_reservation'),
                [
                    'sku IN (?)' => ['SKU-1', 'SKU-2', 'SKU-3', 'SKU-4', 'SKU-5', 'SKU-6', 'SKU-7', ]
                ]
            );
        parent::tearDown();
    }

    /**
     * Tests inventory_stock_* is_salable value when backorders are globally disabled.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 0
     * @dataProvider backordersDisabledDataProvider
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     */
    public function testBackordersDisabled(string $sku, int $stockId, $expectedData): void
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * Tests inventory_stock_* is_salable value when backorders are globally enabled.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 1
     * @dataProvider backordersGlobalEnabledDataProvider
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     */
    public function testGlobalBackordersEnabled(string $sku, int $stockId, $expectedData): void
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * Tests inventory_stock_* is_salable value when backorders for stock items are disabled.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 1
     * @dataProvider backordersDisabledDataProvider
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     */
    public function testStockItemBackordersDisabled(string $sku, int $stockId, $expectedData): void
    {
        $this->updateStockItem(
            $sku,
            [
                StockItemInterface::USE_CONFIG_BACKORDERS => false,
                StockItemInterface::BACKORDERS => StockItemConfigurationInterface::BACKORDERS_NO,
            ],
        );

        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * Tests inventory_stock_* is_salable value when backorders for stock items are enabled.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 0
     * @dataProvider backordersEnabledDataProvider
     *
     * @param string $sku
     * @param int $stockId
     * @param int $itemBackorders
     * @param array|null $expectedData
     */
    public function testStockItemBackordersEnabled(string $sku, int $stockId, int $itemBackorders, $expectedData): void
    {
        $this->updateStockItem(
            $sku,
            [
                StockItemInterface::USE_CONFIG_BACKORDERS => false,
                StockItemInterface::BACKORDERS => $itemBackorders,
            ],
        );

        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * Tests "is_salable" when backorders is enabled and reservations qty > 0
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 0
     * @dataProvider backordersWithReservationsDataProvider
     * @param string $sku
     * @param int $stockId
     * @param array $stockConfig
     * @param array|null $expectedData
     */
    public function testBackordersWithReservations(
        string $sku,
        int $stockId,
        array $stockConfig,
        ?array $expectedData
    ): void {
        $this->appendReservations->execute(
            [
                $this->reservationBuilder->setStockId($stockId)->setSku($sku)->setQuantity(-20)->build(),
                $this->reservationBuilder->setStockId($stockId)->setSku($sku)->setQuantity(-30)->build(),
                $this->reservationBuilder->setStockId($stockId)->setSku($sku)->setQuantity(-50)->build(),
            ]
        );
        $this->updateStockItem($sku, $stockConfig);

        self::assertEquals($expectedData, $this->getStockItemData->execute($sku, $stockId));
    }

    /**
     * @return array
     */
    public function backordersWithReservationsDataProvider(): array
    {
        return  [
            'infinite backorders' => [
                'SKU-1',
                10,
                [
                    StockItemInterface::USE_CONFIG_MIN_QTY => false,
                    StockItemInterface::MIN_QTY => 0,
                    StockItemInterface::USE_CONFIG_BACKORDERS => false,
                    StockItemInterface::BACKORDERS => StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                ],
                [
                    GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1
                ]
            ],
            'limited backorders is not exceeded' => [
                'SKU-1',
                10,
                [
                    StockItemInterface::USE_CONFIG_MIN_QTY => false,
                    StockItemInterface::MIN_QTY => -100,
                    StockItemInterface::USE_CONFIG_BACKORDERS => false,
                    StockItemInterface::BACKORDERS => StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                ],
                [
                    GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1
                ]
            ],
            'limited backorders is exceeded' => [
                'SKU-1',
                10,
                [
                    StockItemInterface::USE_CONFIG_MIN_QTY => false,
                    StockItemInterface::MIN_QTY => -91.5,
                    StockItemInterface::USE_CONFIG_BACKORDERS => false,
                    StockItemInterface::BACKORDERS => StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                ],
                [
                    GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 0
                ]
            ]
        ];
    }

    /**
     * Data provider for test with global enabled backorders.
     *
     * @return array
     */
    public function backordersGlobalEnabledDataProvider(): array
    {
        return [
            ['SKU-1', 10, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, null],
            // SKU-3 is assigned only to eu-2 with status out-of-stock
            ['SKU-3', 10, [GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0]],
        ];
    }

    /**
     * Data provider for test with enabled backorders.
     *
     * @return array
     */
    public function backordersEnabledDataProvider(): array
    {
        return [
            [
                'SKU-1',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                [
                    GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1
                ]
            ],
            [
                'SKU-1',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                [
                    GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1
                ]
            ],
            [
                'SKU-2',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                null
            ],
            [
                'SKU-2',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                null
            ],
            [
                // SKU-3 is assigned only to eu-2 with status out-of-stock
                'SKU-3',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                [
                    GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0
                ]
            ],
            [
                // SKU-3 is assigned only to eu-2 with status out-of-stock
                'SKU-3',
                10,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                [
                    GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0
                ]
            ],
        ];
    }

    /**
     * Data provider for test with disabled backorders.
     *
     * @return array
     */
    public function backordersDisabledDataProvider(): array
    {
        return [
            ['SKU-1', 10, [GetStockItemDataInterface::QUANTITY => 8.5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, null],
            ['SKU-3', 10, [GetStockItemDataInterface::QUANTITY => 0, GetStockItemDataInterface::IS_SALABLE => 0]],
        ];
    }

    /**
     * Set products backorder status.
     *
     * @param string $sku
     * @param array $data
     */
    private function updateStockItem(string $sku, array $data): void
    {
        $product = $this->productRepository->get($sku);
        $stockItemSearchCriteria = $this->stockItemCriteriaFactory->create();
        $stockItemSearchCriteria->setProductsFilter($product->getId());
        $stockItemsCollection = $this->stockItemRepository->getList($stockItemSearchCriteria);

        /** @var \Magento\CatalogInventory\Model\Stock\Item $legacyStockItem */
        $legacyStockItem = current($stockItemsCollection->getItems());
        foreach ($data as $name => $value) {
            $legacyStockItem->setDataUsingMethod($name, $value);
        }
        $this->stockItemRepository->save($legacyStockItem);

        $sourceItem = $this->getSourceItemBySku($sku);
        $this->sourceItemsSave->execute([$sourceItem]);
    }

    /**
     * Get source item by products sku.
     *
     * @param string $sku
     * @return SourceItemInterface
     */
    private function getSourceItemBySku(string $sku): SourceItemInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $sku)
            ->create();
        $sourceItemSearchResult = $this->sourceItemRepository->getList($searchCriteria);

        return current($sourceItemSearchResult->getItems());
    }
}
