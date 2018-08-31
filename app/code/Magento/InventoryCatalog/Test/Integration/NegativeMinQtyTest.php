<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NegativeMinQtyTest extends TestCase
{
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
        $this->isProductSalableForRequestedQty = Bootstrap::getObjectManager()->get(
            IsProductSalableForRequestedQtyInterface::class
        );
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
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @dataProvider isProductSalableForRequestedQtyWithBackordersEnabledAtProductLevelDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testIsProductSalableForRequestedQtyWithBackordersEnabledAtProductLevel(
        $sku,
        $stockId,
        $minQty,
        $requestedQty,
        $expectedSalability
    ) {
        $this->markTestSkipped('Rework test, as backorders now in source item configuration.');
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setUseConfigBackorders(false);
        $stockItemConfiguration->setBackorders(StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY);
        $stockItemConfiguration->setUseConfigMinQty(false);
        $stockItemConfiguration->setMinQty($minQty);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $this->assertEquals(
            $expectedSalability,
            $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty)->isSalable()
        );
    }

    public function isProductSalableForRequestedQtyWithBackordersEnabledAtProductLevelDataProvider()
    {
        return [
            'salable_qty' => ['SKU-1', 10, -4.5, 13, true],
            'not_salable_qty' => ['SKU-1', 10, -4.5, 14, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoConfigFixture default_store cataloginventory/item_options/min_qty -4.5
     * @magentoConfigFixture default_store cataloginventory/item_options/backorders 1
     * @dataProvider isProductSalableForRequestedQtyWithBackordersEnabledGloballyDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testIsProductSalableForRequestedQtyWithBackordersEnabledGlobally(
        $sku,
        $stockId,
        $requestedQty,
        $expectedSalability
    ) {
        $this->markTestSkipped('Rework test, as backorders now in source item configuration.');
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setUseConfigBackorders(true);
        $stockItemConfiguration->setUseConfigMinQty(true);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $this->assertEquals(
            $expectedSalability,
            $this->isProductSalableForRequestedQty->execute($sku, $stockId, $requestedQty)->isSalable()
        );
    }

    public function isProductSalableForRequestedQtyWithBackordersEnabledGloballyDataProvider()
    {
        return [
            'salable_qty' => ['SKU-1', 10, 13, true],
            'not_salable_qty' => ['SKU-1', 10, 14, false],
        ];
    }
}
