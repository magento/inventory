<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\Model\Product\Type\Configurable;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable\IsSalableOptionPlugin;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class IsSalableOptionPluginTest extends TestCase
{
    /**
     * @var IsSalableOptionPlugin
     */
    private IsSalableOptionPlugin $plugin;

    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalableMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StockResolverInterface|MockObject
     */
    private $stockResolverMock;

    /**
     * @var StockConfigurationInterface|MockObject
     */
    private $stockConfigurationMock;

    /**
     * @var Configurable|MockObject
     */
    private $configurableMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMock;

    /**
     * @var StockInterface|MockObject
     */
    private $stockMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->areProductsSalableMock = $this->createMock(AreProductsSalableInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->stockResolverMock = $this->createMock(StockResolverInterface::class);
        $this->stockConfigurationMock = $this->createMock(StockConfigurationInterface::class);
        $this->configurableMock = $this->createMock(Configurable::class);
        $this->websiteMock = $this->createMock(WebsiteInterface::class);
        $this->stockMock = $this->createMock(StockInterface::class);

        $this->plugin = new IsSalableOptionPlugin(
            $this->areProductsSalableMock,
            $this->storeManagerMock,
            $this->stockResolverMock,
            $this->stockConfigurationMock
        );
    }

    public function testAllProductsAreSalable()
    {
        $products = $this->createProducts(['sku1' => true, 'sku2' => true]);
        $this->mockAreProductsSalable(['sku1' => true, 'sku2' => true]);
        $this->createAdditionalMocks();

        $result = $this->plugin->afterGetUsedProducts($this->configurableMock, $products);

        $this->assertEquals(2, count($result));
        foreach ($result as $product) {
            $this->assertEquals(1, $product->getIsSalable());
        }
    }

    /**
     * @dataProvider productSalabilityDataProvider
     */
    public function testSomeProductsAreNotSalable(bool $isShowOutOfStock, int $expectedCount)
    {
        $this->stockConfigurationMock->method('isShowOutOfStock')->willReturn($isShowOutOfStock);

        $products = $this->createProducts(['sku1' => true, 'sku2' => false, 'sku3' => true]);
        $this->mockAreProductsSalable(['sku1' => true, 'sku2' => false, 'sku3' => true]);
        $this->createAdditionalMocks();

        $result = $this->plugin->afterGetUsedProducts($this->configurableMock, $products);

        $this->assertEquals($expectedCount, count($result));
        foreach ($result as $product) {
            if ($product->getSku() === 'sku2') {
                $this->assertEquals(0, $product->getIsSalable());
            } else {
                $this->assertEquals(1, $product->getIsSalable());
            }
        }
    }

    public function productSalabilityDataProvider(): array
    {
        return [
            'Hide Out Of Stock' => [false, 2],
            'Show Out Of Stock' => [true, 3],
        ];
    }

    public function testNoProductsAreSalable()
    {
        $products = $this->createProducts(['sku1' => false, 'sku2' => false]);
        $this->mockAreProductsSalable(['sku1' => false, 'sku2' => false]);
        $this->createAdditionalMocks();

        $result = $this->plugin->afterGetUsedProducts($this->configurableMock, $products);

        $this->assertEquals(0, count($result));
        foreach ($result as $product) {
            $this->assertEquals(0, $product->getIsSalable());
        }
    }

    public function testEmptyProductsArray()
    {
        $products = [];

        $result = $this->plugin->afterGetUsedProducts($this->configurableMock, $products);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    private function createProducts(array $productData): array
    {
        return array_map(function ($sku, $isSalable) {
            $productMock = $this->createMock(Product::class);
            $productMock->method('getSku')->willReturn($sku);
            $productMock->method('getIsSalable')->willReturn($isSalable);
            return $productMock;
        }, array_keys($productData), $productData);
    }

    private function mockAreProductsSalable(array $skus): void
    {
        $salableResults = [];

        // Handle a map of SKUs to their salable statuses
        foreach ($skus as $sku => $isSalable) {
            $salableResultMock = $this->createMock(IsProductSalableResultInterface::class);
            $salableResultMock->method('getSku')->willReturn($sku);
            $salableResultMock->method('isSalable')->willReturn($isSalable);
            $salableResults[] = $salableResultMock;
        }

        $this->areProductsSalableMock->method('execute')->willReturn($salableResults);
    }

    private function createAdditionalMocks(): void
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn('website_code');
        $this->stockResolverMock->expects($this->once())
            ->method('execute')
            ->with(SalesChannelInterface::TYPE_WEBSITE, 'website_code')
            ->willReturn($this->stockMock);
        $this->stockMock->expects($this->once())
            ->method('getStockId')
            ->willReturn(1);
    }
}
