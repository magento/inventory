<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalable;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ManageStockConditionTest extends TestCase
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

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

    protected function setUp()
    {
        parent::setUp();

        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalableInterface::class);
        $this->getStockConfiguration = Bootstrap::getObjectManager()->get(GetStockConfigurationInterface::class);
        $this->saveStockConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);
        $this->stockItemConfigurationInterfaceFactory = Bootstrap::getObjectManager()->get(
            StockItemConfigurationInterfaceFactory::class
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
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider executeWithManageStockFalseDataProvider
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testExecuteWithManageStockFalse(string $sku, int $stockId, bool $expectedResult)
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
        $isSalable = $this->isProductSalable->execute($sku, $stockId);

        self::assertEquals($expectedResult, $isSalable);
    }

    /**
     * @return array
     */
    public function executeWithManageStockFalseDataProvider(): array
    {
        return [ // Update tear down if you add more stock ids or skus
            ['SKU-1', 10, true],
            ['SKU-1', 20, false],
            ['SKU-1', 30, true],
            ['SKU-2', 10, false],
            ['SKU-2', 20, true],
            ['SKU-2', 30, true],
            ['SKU-3', 10, true],
            ['SKU-3', 20, false],
            ['SKU-3', 30, true],
        ];
    }

    protected function tearDown()
    {
        $stocksIdsToClean = [10, 20, 30];
        $skusToClean = ['SKU-1', 'SKU-2', 'SKU-3'];

        foreach ($stocksIdsToClean as $stockId) {
            foreach ($skusToClean as $sku) {
                $stockConfiguration = $this->stockItemConfigurationInterfaceFactory->create();

                $this->saveStockConfiguration->forStock($stockId, $stockConfiguration);
                $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockConfiguration);
            }
        }
    }
}
