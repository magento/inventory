<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ManageStockConditionTest extends TestCase
{
    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->areProductsSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(AreProductsSalableForRequestedQtyInterface::class);
        $this->isProductSalableForRequestedQtyRequestFactory = Bootstrap::getObjectManager()->get(
            IsProductSalableForRequestedQtyRequestInterfaceFactory::class
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture default_store cataloginventory/item_options/manage_stock 0
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
    public function testExecuteWithManageStockFalse(string $sku, int $stockId, float $qty, bool $expectedResult): void
    {
        $request = $this->isProductSalableForRequestedQtyRequestFactory->create(
            [
                'sku' => $sku,
                'qty' => $qty,
            ]
        );
        $result = $this->areProductsSalableForRequestedQty->execute([$request], $stockId);
        $result = current($result);
        self::assertEquals($expectedResult, $result->isSalable());
    }

    /**
     * @return array
     */
    public static function executeWithManageStockFalseDataProvider(): array
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
