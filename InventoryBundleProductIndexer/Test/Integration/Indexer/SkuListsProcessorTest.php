<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Integration\Indexer;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Indexer\Test\Fixture\Indexer as IndexerFixture;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryReservations\Test\Fixture\Reservation as ReservationFixture;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Fixture\ScopeFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[
    CoversClass(SelectBuilder::class),
]
class SkuListsProcessorTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var SkuListsProcessor
     */
    private $skuListsProcessor;

    /**
     * @var SkuListInStockFactory
     */
    private $skuListInStockFactory;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    protected function setUp(): void
    {
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->skuListsProcessor = Bootstrap::getObjectManager()->create(SkuListsProcessor::class);
        $this->skuListInStockFactory = Bootstrap::getObjectManager()->get(SkuListInStockFactory::class);
        $this->getSourceItemsBySku = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuInterface::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 's2'], 'source2'),
        DataFixture(SourceFixture::class, ['source_code' => 's3'], 'source3'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source3.source_code$'],
            ]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple1',
                'website_ids' => ['1', '$website2.id$'],
                'stock_item' => ['use_config_min_qty' => 0, 'min_qty' => 2],
            ],
            's1'
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2', 'website_ids' => ['1', '$website2.id$']], 's2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple3', 'website_ids' => ['1', '$website2.id$']], 's3'),
        DataFixture(ProductFixture::class, ['sku' => 'simple4', 'website_ids' => ['1', '$website2.id$']], 's4'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$s1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 5],
                ['sku' => '$s1.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 0],
                ['sku' => '$s2.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 5],
                ['sku' => '$s2.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 0],
                ['sku' => '$s3.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 3],
                ['sku' => '$s3.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 0],
                ['sku' => '$s4.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 3],
                ['sku' => '$s4.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 0],
            ]
        ),
        DataFixture(
            ReservationFixture::class,
            [
                'stock_id' => '$stock2.stock_id$',
                'sku' => '$s2.sku$',
                'quantity' => -2,
                'metadata' => [
                    'event_type' => 'shipment_created',
                    'object_type' => 'order',
                    'object_id' => '1',
                    'object_increment_id' => '100000001',
                ],
            ]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s1.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link1'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s2.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link2'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s3.sku$', 'qty' => 3, 'can_change_quantity' => 1],
            'link3'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s4.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link4'
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link1$', '$link2$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link3$']], 'opt2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link4$'], 'required' => false], 'opt3'),
        DataFixture(
            BundleProductFixture::class,
            [
                'sku' => 'bundle1',
                'website_ids' => ['1', '$website2.id$'],
                '_options' => ['$opt1$', '$opt2$', '$opt3$'],
                'shipment_type' => 1,
            ]
        ),
        DataFixture(IndexerFixture::class),
        DataProvider('executeListDataProvider')
    ]
    /**
     * @param array $newSourceData
     * @param bool $expectedStockStatus
     * @return void
     */
    public function testExecuteList(array $newSourceData, bool $expectedStockStatus): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');

        foreach ($newSourceData as $sku => $newQuantities) {
            $sourceItems = $this->getSourceItemsBySku->execute($sku);
            foreach ($sourceItems as $sourceItem) {
                if (isset($newQuantities[$sourceItem->getSourceCode()])) {
                    $sourceItem->setQuantity($newQuantities[$sourceItem->getSourceCode()]);
                }
            }
            $this->sourceItemsSave->execute($sourceItems);
        }

        $skuListInStock = $this->skuListInStockFactory->create(
            ['stockId' => $stock->getStockId(), 'skuList' => ['bundle1']]
        );
        $this->skuListsProcessor->reindexList([$skuListInStock]);

        $bundleStockItem = $this->getStockItemData->execute('bundle1', $stock->getStockId());
        self::assertEquals($expectedStockStatus, (bool) $bundleStockItem[GetStockItemDataInterface::IS_SALABLE]);
    }

    public static function executeListDataProvider(): array
    {
        return [
            [[], true],
            [
                [
                    'simple1' => ['s2' => 3, 's3' => 2],
                    'simple2' => ['s2' => 0, 's3' => 0],
                ],
                true,
            ],
            [
                [
                    'simple1' => ['s2' => 0, 's3' => 0],
                    'simple2' => ['s2' => 2, 's3' => 3],
                ],
                true,
            ],
            [
                ['simple3' => ['s2' => 1, 's3' => 2]],
                true,
            ],
            [
                ['simple3' => ['s2' => 1, 's3' => 0]],
                true,
            ],
            [
                ['simple4' => ['s2' => 0, 's3' => 0]],
                true,
            ],
            [
                [
                    'simple1' => ['s2' => 4, 's3' => 0],
                    'simple2' => ['s2' => 0, 's3' => 4],
                ],
                false,
            ],
            [
                [
                    'simple1' => ['s2' => 2, 's3' => 2],
                    'simple2' => ['s2' => 2, 's3' => 2],
                ],
                false,
            ],
            [
                ['simple3' => ['s2' => 0, 's3' => 0]],
                false,
            ],
        ];
    }
}
