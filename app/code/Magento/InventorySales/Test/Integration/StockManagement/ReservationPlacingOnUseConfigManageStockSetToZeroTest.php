<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\StockManagement;

use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ReservationPlacingOnUseConfigManageStockSetToZeroTest extends TestCase
{
    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown()
    {
        Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class)->execute();
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoConfigFixture default/cataloginventory/item_options/manage_stock 0
     *
     * @magentoDbIsolation disabled
     */
    public function testPlacingReservationOnUseConfigManageStockSetToZero()
    {
        $appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
        $reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $getReservationQuantity = Bootstrap::getObjectManager()->get(GetReservationsQuantityInterface::class);
        $getStockConfiguration = Bootstrap::getObjectManager()->get(GetStockConfigurationInterface::class);
        $saveStockConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);
        $stockConfiguration = $getStockConfiguration->forStock(10);
        $stockConfiguration->setIsQtyDecimal(false);
        $stockConfiguration->setIsDecimalDivided(false);
        $stockConfiguration->setManageStock(null);
        $saveStockConfiguration->forStock(10, $stockConfiguration);
        $stockItemConfiguration = $getStockConfiguration->forStockItem('SKU-4', 10);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setManageStock(null);
        $saveStockConfiguration->forStockItem('SKU-4', 10, $stockItemConfiguration);

        $appendReservations->execute(
            [
                $reservationBuilder->setStockId(10)->setSku('SKU-4')->setQuantity(2)->build()
            ]
        );

        self::assertEquals(0, $getReservationQuantity->execute('SKU-4', 10));
    }
}
