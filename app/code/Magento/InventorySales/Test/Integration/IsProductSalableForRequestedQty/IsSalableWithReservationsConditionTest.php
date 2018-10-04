<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class IsSalableWithReservationsConditionTest extends TestCase
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
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var StockItemConfigurationInterfaceFactory
     */
    private $stockItemConfigurationInterfaceFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->isProductSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(IsProductSalableForRequestedQtyInterface::class);
        $this->getStockConfiguration = Bootstrap::getObjectManager()->get(
            GetStockConfigurationInterface::class
        );
        $this->saveStockConfiguration = Bootstrap::getObjectManager()->get(
            SaveStockConfigurationInterface::class
        );
        $this->stockItemConfigurationInterfaceFactory = Bootstrap::getObjectManager()->get(
            StockItemConfigurationInterfaceFactory::class
        );
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testProductIsSalable(string $sku, int $stockId, float $qty, bool $isSalable)
    {
        self::assertEquals(
            $isSalable,
            $this->isProductSalableForRequestedQty->execute($sku, $stockId, $qty)->isSalable()
        );
    }

    /**
     * @return array
     */
    public function productIsSalableDataProvider(): array
    {
        return [ // Update tear down if you add more stock ids or skus
            ['SKU-1', 10, 1, true],
            ['SKU-1', 20, 1, false],
            ['SKU-1', 30, 1, true],
            ['SKU-2', 10, 1, false],
            ['SKU-2', 20, 1, true],
            ['SKU-2', 30, 1, true],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 20, 1, false],
            ['SKU-3', 30, 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoConfigFixture default/cataloginventory/item_options/min_qty 5
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableWithUseConfigMinQtyDataProvider
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testProductIsSalableWithUseConfigMinQty(string $sku, int $stockId, float $qty, bool $isSalable)
    {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setMinQty(null);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $isSalableResult = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $qty)->isSalable();

        self::assertEquals($isSalable, $isSalableResult);
    }

    /**
     * @return array
     */
    public function productIsSalableWithUseConfigMinQtyDataProvider(): array
    {
        return [ // Update tear down if you add more stock ids or skus
            ['SKU-1', 10, 3, true],
            ['SKU-1', 10, 4, false],
            ['SKU-1', 30, 3, true],
            ['SKU-1', 30, 4, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 30, 1, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 30, 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableWithMinQtyDataProvider
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testProductIsSalableWithMinQty(string $sku, int $stockId, float $qty, bool $isSalable)
    {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setMinQty(5.00);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $isSalableResult = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $qty)->isSalable();

        self::assertEquals($isSalable, $isSalableResult);
    }

    /**
     * @return array
     */
    public function productIsSalableWithMinQtyDataProvider(): array
    {
        return [ // Update tear down if you add more stock ids or skus
            ['SKU-1', 10, 3, true],
            ['SKU-1', 10, 4, false],
            ['SKU-1', 30, 3, true],
            ['SKU-1', 30, 4, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 30, 1, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 30, 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testProductIsOutOfStockIfReservationsArePresent()
    {
        // emulate order placement (reserve -8.5 units)
        $this->appendReservations->execute([
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(-8.5)->build(),
        ]);
        self::assertFalse($this->isProductSalableForRequestedQty->execute('SKU-1', 10, 1)->isSalable());

        $this->appendReservations->execute([
            // unreserve 8.5 units for cleanup
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(8.5)->build(),
        ]);
        $this->cleanupReservations->execute();
    }

    protected function tearDown()
    {
        $stocksIdsToClean = [10, 20, 30];
        $skusToClean = ['SKU-1', 'SKU-2', 'SKU-3'];

        $stockConfiguration = $this->stockItemConfigurationInterfaceFactory->create();

        foreach ($stocksIdsToClean as $stockId) {
            $this->saveStockConfiguration->forStock($stockId, $stockConfiguration);
            foreach ($skusToClean as $sku) {
                $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockConfiguration);
            }
        }
    }
}
