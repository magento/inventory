<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Stock;

use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetProductSalableQtyTest extends TestCase
{
    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    protected function setUp(): void
    {
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->getProductSalableQty = Bootstrap::getObjectManager()->get(
            GetProductSalableQtyInterface::class
        );
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qty
     *
     * @dataProvider getProductQuantityProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testGetProductQuantity(string $sku, int $stockId, float $qty)
    {
        self::assertEquals($qty, $this->getProductSalableQty->execute($sku, $stockId));
    }

    /**
     * @return array
     */
    public function getProductQuantityProvider(): array
    {
        return [
            ['SKU-1', 10, 8.5],
            ['SKU-1', 20, 0],
            ['SKU-1', 30, 8.5],
            ['SKU-2', 10, 0],
            ['SKU-2', 20, 5],
            ['SKU-2', 30, 5],
            ['SKU-3', 10, 0],
            ['SKU-3', 20, 0],
            ['SKU-3', 30, 0],
            ['SKU-6', 10, 3],
        ];
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testGetProductQuantityIfReservationsArePresent()
    {
        $this->appendReservations->execute([
            // emulate order placement reserve 5 units)
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(-5)->build(),
            // emulate partial order canceling (1.5 units)
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(1.5)->build(),
        ]);
        self::assertEquals(5, $this->getProductSalableQty->execute('SKU-1', 10));

        $this->appendReservations->execute([
            // unreserved 3.5 units for cleanup
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(3.5)->build(),
        ]);
    }

    /**
     * Verify 'Out of stock' source items will show '0' salable qty in case global 'out of stock' threshold is negative.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoConfigFixture default_store cataloginventory/item_options/min_qty -10
     * @magentoConfigFixture default_store cataloginventory/item_options/backorders 1
     *
     * @magentoDbIsolation disabled
     */
    public function testGetSalableQuantityWithGlobalBackordersAndOutOfStockSourceItems()
    {
        $sku = 'SKU-2';
        self::assertEquals(15, $this->getProductSalableQty->execute($sku, 20));
        $this->setSourceItemsToOutOfStock($sku);
        self::assertEquals(0, $this->getProductSalableQty->execute($sku, 20));
    }

    /**
     * Verify 'Out of stock' source items will show '0' salable qty if stock item 'out of stock' threshold is negative.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testGetSalableQuantityWithBackordersAndOutOfStockSourceItems()
    {
        $sku = 'SKU-2';
        $this->enableBackorders();
        self::assertEquals(15, $this->getProductSalableQty->execute($sku, 20));
        $this->setSourceItemsToOutOfStock($sku);
        self::assertEquals(0, $this->getProductSalableQty->execute($sku, 20));
    }

    /**
     * Enable backorders and negative 'out or stock' threshold for stock item.
     *
     * @return void
     */
    private function enableBackorders(): void
    {
        $getLegacyItem = Bootstrap::getObjectManager()->get(GetLegacyStockItem::class);
        $stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $legacyStockItem = $getLegacyItem->execute('SKU-2');
        $legacyStockItem->setBackorders(1);
        $legacyStockItem->setUseConfigBackorders(false);
        $legacyStockItem->setMinQty(-10);
        $legacyStockItem->setUseConfigMinQty(false);
        $stockItemRepository->save($legacyStockItem);
    }

    /**
     * Set source items for given product 'out of stock' status.
     *
     * @param string $sku
     * @return void
     */
    private function setSourceItemsToOutOfStock(string $sku): void
    {
        $sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $getSourceItems = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuInterface::class);
        $sourceItems = $getSourceItems->execute($sku);
        foreach ($sourceItems as $sourceItem) {
            $sourceItem->setStatus(0);
        }
        $sourceItemsSave->execute($sourceItems);
    }
}
