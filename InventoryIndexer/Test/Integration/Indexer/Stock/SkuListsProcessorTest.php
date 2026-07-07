<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Indexer\Stock;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager as DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SkuListsProcessorTest extends TestCase
{
    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    protected function setUp(): void
    {
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    /**
     * Product should stay in inventory stock if related product is updated and reindex is run.
     */
    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p1.sku$', 'type' => 'related']]], 'p2'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p1.sku$', 'type' => 'upsell']]], 'p3'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p1.sku$', 'type' => 'crosssell']]], 'p4'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
                ['sku' => '$p2.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 200],
                ['sku' => '$p3.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 300],
                ['sku' => '$p4.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 400],
            ]
        ),
    ]
    public function testReindexListForRelatedProducts()
    {
        $fixtures = DataFixtureStorageManager::getStorage();

        $stock = $fixtures->get('stock2');
        $stockId = $stock->getStockId();

        $sku1 = $fixtures->get('p1')->getSku();
        $sku2 = $fixtures->get('p2')->getSku();
        $sku3 = $fixtures->get('p3')->getSku();
        $sku4 = $fixtures->get('p4')->getSku();

        // All products should be in inventory_stock_2 table, none should be deleted at reindex.
        // Reindex is triggered when source is assigned to products
        self::assertEquals(
            [GetStockItemDataInterface::QUANTITY => 100, GetStockItemDataInterface::IS_SALABLE => 1],
            $this->getStockItemData->execute($sku1, $stockId),
            "First product should be present in inventory_stock_" . $stockId
        );
        self::assertEquals(
            [GetStockItemDataInterface::QUANTITY => 200, GetStockItemDataInterface::IS_SALABLE => 1],
            $this->getStockItemData->execute($sku2, $stockId),
            "Second product should be present in inventory_stock_" . $stockId
        );
        self::assertEquals(
            [GetStockItemDataInterface::QUANTITY => 300, GetStockItemDataInterface::IS_SALABLE => 1],
            $this->getStockItemData->execute($sku3, $stockId),
            "Third product should be present in inventory_stock_" . $stockId
        );
        self::assertEquals(
            [GetStockItemDataInterface::QUANTITY => 400, GetStockItemDataInterface::IS_SALABLE => 1],
            $this->getStockItemData->execute($sku4, $stockId),
            "Fourth product should be present in inventory_stock_" . $stockId
        );
    }
}
