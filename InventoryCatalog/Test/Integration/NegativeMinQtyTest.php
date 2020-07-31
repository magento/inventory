<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NegativeMinQtyTest extends TestCase
{
    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var SaveStockItemConfigurationInterface
     */
    private $saveStockItemConfiguration;

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
        $this->isProductSalableForRequestedQtyRequestFactory = Bootstrap::getObjectManager()
            ->get(IsProductSalableForRequestedQtyRequestInterfaceFactory::class);
        $this->areProductsSalableForRequestedQty = Bootstrap::getObjectManager()->get(
            AreProductsSalableForRequestedQtyInterface::class
        );
        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(
            GetStockItemConfigurationInterface::class
        );
        $this->saveStockItemConfiguration = Bootstrap::getObjectManager()->get(
            SaveStockItemConfigurationInterface::class
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @dataProvider isProductSalableForRequestedQtyWithBackordersEnabledAtProductLevelDataProvider
     *
     * @magentoDbIsolation disabled
     * @param string $sku
     * @param int $stockId
     * @param float $minQty
     * @param float $requestedQty
     * @param bool $expectedSaleability
     * @return void
     */
    public function testIsProductSalableForRequestedQtyWithBackordersEnabledAtProductLevel(
        string $sku,
        int $stockId,
        float $minQty,
        float $requestedQty,
        bool $expectedSaleability
    ): void {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setUseConfigBackorders(false);
        $stockItemConfiguration->setBackorders(StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY);
        $stockItemConfiguration->setUseConfigMinQty(false);
        $stockItemConfiguration->setMinQty($minQty);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        $request = $this->isProductSalableForRequestedQtyRequestFactory->create(
            [
                'sku' => $sku,
                'qty' => $requestedQty,
            ]
        );
        $result = $this->areProductsSalableForRequestedQty->execute([$request], $stockId);
        $result = current($result);

        $this->assertEquals(
            $expectedSaleability,
            $result->isSalable()
        );
    }

    /**
     * Get test data.
     *
     * @return array
     */
    public function isProductSalableForRequestedQtyWithBackordersEnabledAtProductLevelDataProvider(): array
    {
        return [
            'salable_qty' => ['SKU-1', 10, -4.5, 13, true],
            'not_salable_qty' => ['SKU-1', 10, -4.5, 14, false],
        ];
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoConfigFixture default_store cataloginventory/item_options/min_qty -4.5
     * @magentoConfigFixture default_store cataloginventory/item_options/backorders 1
     * @dataProvider isProductSalableForRequestedQtyWithBackordersEnabledGloballyDataProvider
     *
     * @magentoDbIsolation disabled
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @param bool $expectedSaleability
     * @return void
     */
    public function testIsProductSalableForRequestedQtyWithBackordersEnabledGlobally(
        string $sku,
        int $stockId,
        float $requestedQty,
        bool $expectedSaleability
    ): void {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setUseConfigBackorders(true);
        $stockItemConfiguration->setUseConfigMinQty(true);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        $request = $this->isProductSalableForRequestedQtyRequestFactory->create(
            [
                'sku' => $sku,
                'qty' => $requestedQty,
            ]
        );
        $result = $this->areProductsSalableForRequestedQty->execute([$request], $stockId);
        $result = current($result);

        $this->assertEquals(
            $expectedSaleability,
            $result->isSalable()
        );
    }

    /**
     * Get test data.
     *
     * @return array
     */
    public function isProductSalableForRequestedQtyWithBackordersEnabledGloballyDataProvider(): array
    {
        return [
            'salable_qty' => ['SKU-1', 10, 13, true],
            'not_salable_qty' => ['SKU-1', 10, 14, false],
        ];
    }
}
