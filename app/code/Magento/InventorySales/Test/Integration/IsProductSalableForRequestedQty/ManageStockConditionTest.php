<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ManageStockConditionTest extends TestCase
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

        $this->isProductSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(IsProductSalableForRequestedQtyInterface::class);
        $this->getStockConfiguration = Bootstrap::getObjectManager()->get(GetStockConfigurationInterface::class);
        $this->saveStockConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);
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
     * @param float $qty
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider executeWithManageStockFalseDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithManageStockFalse(string $sku, int $stockId, float $qty, bool $expectedResult)
    {
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $stockConfiguration->setManageStock(!$expectedResult);
        $stockConfiguration->setIsDecimalDivided(false);
        $stockConfiguration->setIsQtyDecimal(false);
        $this->saveStockConfiguration->forStock($stockId, $stockConfiguration);
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setManageStock(null);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);
        $isSalable = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $qty)->isSalable();
        self::assertEquals($expectedResult, $isSalable);
    }

    /**
     * @return array
     */
    public function executeWithManageStockFalseDataProvider(): array
    {
        return [
            ['SKU-1', 10, 1, true],
            ['SKU-1', 20, 1, false],
            ['SKU-1', 30, 1, true],
            ['SKU-2', 10, 1, false],
            ['SKU-2', 20, 1, true],
            ['SKU-2', 30, 1, true],
            ['SKU-3', 10, 1, true],
            ['SKU-3', 20, 1, false],
            ['SKU-3', 30, 1, true],
        ];
    }
}
