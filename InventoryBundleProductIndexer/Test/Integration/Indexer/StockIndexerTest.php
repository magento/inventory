<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Integration\Indexer;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class StockIndexerTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

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
        $this->stockIndexer = Bootstrap::getObjectManager()->create(StockIndexer::class);
        $this->getSourceItemsBySku = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuInterface::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    #[
        DbIsolation(false),
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
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),

        DataFixture(ProductFixture::class, ['sku' => 'simple1'], 's1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple2'], 's2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple3'], 's3'),
        DataFixture(ProductFixture::class, ['sku' => 'simple4'], 's4'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$s1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
                ['sku' => '$s1.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 100],
                ['sku' => '$s2.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
                ['sku' => '$s2.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 100],
                ['sku' => '$s3.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
                ['sku' => '$s3.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 100],
                ['sku' => '$s4.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
                ['sku' => '$s4.sku$', 'source_code' => '$source3.source_code$', 'quantity' => 100],
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
            ['sku' => 'bundle1', '_options' => ['$opt1$', '$opt2$', '$opt3$'], 'shipment_type' => 1]
        ),
    ]
    /**
     * @dataProvider executeListDataProvider
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

        $this->stockIndexer->executeList([$stock->getStockId()], ['bundle1']);
        $bundleStockItem = $this->getStockItemData->execute('bundle1', $stock->getStockId());
        self::assertEquals($expectedStockStatus, (bool) $bundleStockItem[GetStockItemDataInterface::IS_SALABLE]);
    }

    public static function executeListDataProvider(): array
    {
        return [
            [
                [
                    'simple1' => ['s2' => 3, 's3' => 0],
                    'simple2' => ['s2' => 3, 's3' => 0],
                    'simple3' => ['s2' => 3, 's3' => 0],
                    'simple4' => ['s2' => 3, 's3' => 0],
                ],
                true,
            ],
            [
                [
                    'simple1' => ['s2' => 1, 's3' => 2],
                    'simple2' => ['s2' => 1, 's3' => 2],
                    'simple3' => ['s2' => 1, 's3' => 2],
                    'simple4' => ['s2' => 1, 's3' => 2],
                ],
                true,
            ],
            [
                [
                    'simple1' => ['s2' => 0, 's3' => 0],
                    'simple3' => ['s2' => 1, 's3' => 1],
                    'simple4' => ['s2' => 0, 's3' => 0],
                ],
                true,
            ],
            [
                [
                    'simple1' => ['s2' => 1, 's3' => 1],
                    'simple2' => ['s2' => 1, 's3' => 1],
                ],
                false,
            ],
            [
                [
                    'simple3' => ['s2' => 0, 's3' => 0],
                ],
                false,
            ],
        ];
    }
}
