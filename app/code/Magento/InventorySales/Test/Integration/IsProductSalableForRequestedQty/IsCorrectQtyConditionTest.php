<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 */
class IsCorrectQtyConditionTest extends TestCase
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockItemConfig;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockItemConfig;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getStockItemConfig = Bootstrap::getObjectManager()->get(GetStockConfigurationInterface::class);
        $this->saveStockItemConfig = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);
        $this->isProductSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(IsProductSalableForRequestedQtyInterface::class);
        $this->getStockConfiguration = Bootstrap::getObjectManager()->get(
            GetStockConfigurationInterface::class
        );
        $this->saveStockConfiguration = Bootstrap::getObjectManager()->get(
            SaveStockConfigurationInterface::class
        );
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithMissingConfigurationDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithMissingConfiguration(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ) {
        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithMissingConfigurationDataProvider(): array
    {
        return [
            ['SKU-2', 10, 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoConfigFixture default/cataloginventory/item_options/min_sale_qty 7
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithUseConfigMinSaleQtyDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithUseConfigMinSaleQty(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithUseConfigMinSaleQtyDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, false],
            ['SKU-1', 10, 7, true],
            ['SKU-1', 10, 8, true],
            ['SKU-2', 10, 1, false],
            ['SKU-2', 10, 7, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 7, false],
            ['SKU-1', 20, 1, false],
            ['SKU-1', 20, 7, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 20, 7, false],
            ['SKU-3', 20, 1, false],
            ['SKU-3', 20, 7, false],
            ['SKU-1', 30, 1, false],
            ['SKU-1', 30, 7, true],
            ['SKU-1', 30, 8, true],
            ['SKU-2', 30, 1, false],
            ['SKU-2', 30, 7, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 7, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     * @dataProvider executeWithMinSaleQtyDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithMinSaleQty(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setMinSaleQty(7.00);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithMinSaleQtyDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, false],
            ['SKU-1', 10, 7, true],
            ['SKU-1', 10, 8, true],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 7, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 20, 7, false],
            ['SKU-1', 30, 1, false],
            ['SKU-1', 30, 7, true],
            ['SKU-1', 30, 8, true],
            ['SKU-2', 30, 1, false],
            ['SKU-2', 30, 7, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 7, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoConfigFixture default/cataloginventory/item_options/max_sale_qty 6
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithUseConfigMaxSaleQtyDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithUseConfigMaxSaleQty(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithUseConfigMaxSaleQtyDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, true],
            ['SKU-1', 10, 6, true],
            ['SKU-1', 10, 7, false],
            ['SKU-2', 10, 1, false],
            ['SKU-2', 10, 7, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 7, false],
            ['SKU-1', 20, 1, false],
            ['SKU-1', 20, 7, false],
            ['SKU-2', 20, 1, true],
            ['SKU-2', 20, 6, false],
            ['SKU-2', 20, 7, false],
            ['SKU-3', 20, 1, false],
            ['SKU-3', 20, 7, false],
            ['SKU-1', 30, 1, true],
            ['SKU-1', 30, 6, true],
            ['SKU-1', 30, 7, false],
            ['SKU-2', 30, 1, true],
            ['SKU-2', 30, 6, false],
            ['SKU-2', 30, 7, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 7, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithMaxSaleQtyDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithMaxSaleQty(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setMaxSaleQty(6.00);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithMaxSaleQtyDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, true],
            ['SKU-1', 10, 6, true],
            ['SKU-1', 10, 7, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 7, false],
            ['SKU-2', 20, 1, true],
            ['SKU-2', 20, 6, false],
            ['SKU-2', 20, 7, false],
            ['SKU-1', 30, 1, true],
            ['SKU-1', 30, 6, true],
            ['SKU-1', 30, 7, false],
            ['SKU-2', 30, 1, true],
            ['SKU-2', 30, 6, false],
            ['SKU-2', 30, 7, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 7, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoConfigFixture default/cataloginventory/item_options/enable_qty_increments 1
     * @magentoConfigFixture default/cataloginventory/item_options/qty_increments 3
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithUseConfigQtyIncrementsDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithUseConfigQtyIncrements(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithUseConfigQtyIncrementsDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, false],
            ['SKU-1', 10, 3, true],
            ['SKU-1', 10, 6, true],
            ['SKU-1', 10, 9, false],
            ['SKU-2', 10, 1, false],
            ['SKU-2', 10, 3, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 3, false],
            ['SKU-1', 20, 1, false],
            ['SKU-1', 20, 3, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 20, 3, true],
            ['SKU-2', 20, 6, false],
            ['SKU-3', 20, 1, false],
            ['SKU-3', 20, 3, false],
            ['SKU-1', 30, 1, false],
            ['SKU-1', 30, 3, true],
            ['SKU-1', 30, 6, true],
            ['SKU-1', 30, 9, false],
            ['SKU-2', 30, 1, false],
            ['SKU-2', 30, 3, true],
            ['SKU-2', 30, 6, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 3, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param int $requestedQty
     * @param bool $expectedResult
     *
     * @return void
     *
     * @dataProvider executeWithQtyIncrementsDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithQtyIncrements(
        string $sku,
        int $stockId,
        int $requestedQty,
        bool $expectedResult
    ): void {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setEnableQtyIncrements(true);
        $stockItemConfiguration->setQtyIncrements(3.00);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $result = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public function executeWithQtyIncrementsDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, false],
            ['SKU-1', 10, 3, true],
            ['SKU-1', 10, 6, true],
            ['SKU-1', 10, 9, false],
            ['SKU-3', 10, 1, false],
            ['SKU-3', 10, 3, false],
            ['SKU-2', 20, 1, false],
            ['SKU-2', 20, 3, true],
            ['SKU-2', 20, 6, false],
            ['SKU-1', 30, 1, false],
            ['SKU-1', 30, 3, true],
            ['SKU-1', 30, 6, true],
            ['SKU-1', 30, 9, false],
            ['SKU-2', 30, 1, false],
            ['SKU-2', 30, 3, true],
            ['SKU-2', 30, 6, false],
            ['SKU-3', 30, 1, false],
            ['SKU-3', 30, 3, false],
        ];
    }
}
