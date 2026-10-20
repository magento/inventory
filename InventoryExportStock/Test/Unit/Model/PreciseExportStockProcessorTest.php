<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventoryExportStock\Model\GetQtyForNotManageStock;
use Magento\InventoryExportStock\Model\GetStockItemConfiguration;
use Magento\InventoryExportStock\Model\PreciseExportStockProcessor;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreciseExportStockProcessorTest extends TestCase
{
    private const STOCK_ID = 2;

    /**
     * @var CacheInterface|MockObject
     */
    private CacheInterface $cacheMock;

    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private GetProductTypesBySkusInterface $getProductTypesBySkusMock;

    /**
     * @var string[]
     */
    private array $calls = [];

    /**
     * @var PreciseExportStockProcessor
     */
    private PreciseExportStockProcessor $model;

    protected function setUp(): void
    {
        $this->calls = [];
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->getProductTypesBySkusMock = $this->createMock(GetProductTypesBySkusInterface::class);

        $isSourceItemManagementAllowedForSku = $this->createStub(IsSourceItemManagementAllowedForSkuInterface::class);
        $isSourceItemManagementAllowedForSku->method('execute')->willReturnCallback(
            function (string $sku): bool {
                $this->calls[] = 'item:' . $sku;
                return $sku !== 'grouped';
            }
        );

        $getStockItemConfiguration = $this->createStub(GetStockItemConfiguration::class);
        $getStockItemConfiguration->method('execute')->willReturnMap(
            [
                ['simple', $this->createStockItemConfiguration(true)],
                ['no-manage', $this->createStockItemConfiguration(false)],
                ['unassigned', $this->createStockItemConfiguration(true)],
            ]
        );

        $isProductAssignedToStock = $this->createStub(IsProductAssignedToStockInterface::class);
        $isProductAssignedToStock->method('execute')->willReturnMap(
            [
                ['simple', self::STOCK_ID, true],
                ['unassigned', self::STOCK_ID, false],
            ]
        );

        $areProductsSalable = $this->createStub(AreProductsSalableInterface::class);
        $areProductsSalable->method('execute')->willReturnMap(
            [
                [['simple'], self::STOCK_ID, [$this->createSalableResult(true)]],
                [['grouped'], self::STOCK_ID, [$this->createSalableResult(false)]],
            ]
        );

        $getProductSalableQty = $this->createStub(GetProductSalableQtyInterface::class);
        $getProductSalableQty->method('execute')->willReturnMap([['simple', self::STOCK_ID, 7.5]]);

        $this->model = new PreciseExportStockProcessor(
            $isSourceItemManagementAllowedForSku,
            $getProductSalableQty,
            new GetQtyForNotManageStock(3.0),
            $getStockItemConfiguration,
            $isProductAssignedToStock,
            $areProductsSalable,
            $this->cacheMock,
            $this->getProductTypesBySkusMock
        );
    }

    public function testExecuteLoadsWholePageBeforeEvaluatingSkus(): void
    {
        $skus = ['simple', 'grouped', 'no-manage', 'unassigned'];
        $this->cacheMock->expects($this->once())
            ->method('warmup')
            ->with($skus, self::STOCK_ID)
            ->willReturnCallback(
                function (): void {
                    $this->calls[] = 'warmup';
                }
            );
        $this->getProductTypesBySkusMock->expects($this->once())
            ->method('execute')
            ->with($skus)
            ->willReturnCallback(
                function (): array {
                    $this->calls[] = 'types';
                    return [];
                }
            );

        $result = $this->model->execute($this->createProducts($skus), self::STOCK_ID);

        $this->assertSame(
            ['warmup', 'types', 'item:simple', 'item:grouped', 'item:no-manage', 'item:unassigned'],
            $this->calls
        );
        $this->assertSame(
            [
                ['sku' => 'simple', 'qty' => 7.5, 'is_salable' => true],
                ['sku' => 'grouped', 'qty' => 0.0, 'is_salable' => false],
                ['sku' => 'no-manage', 'qty' => 3.0, 'is_salable' => true],
            ],
            $result
        );
    }

    public function testExecuteWithEmptyPageSkipsLoading(): void
    {
        $this->cacheMock->expects($this->never())->method('warmup');
        $this->getProductTypesBySkusMock->expects($this->never())->method('execute');

        $this->assertSame([], $this->model->execute([], self::STOCK_ID));
    }

    /**
     * @param string[] $skus
     * @return ProductInterface[]
     */
    private function createProducts(array $skus): array
    {
        $products = [];
        foreach ($skus as $sku) {
            $product = $this->createStub(ProductInterface::class);
            $product->method('getSku')->willReturn($sku);
            $products[] = $product;
        }

        return $products;
    }

    private function createStockItemConfiguration(bool $isManageStock): StockItemConfigurationInterface
    {
        $configuration = $this->createStub(StockItemConfigurationInterface::class);
        $configuration->method('isManageStock')->willReturn($isManageStock);

        return $configuration;
    }

    private function createSalableResult(bool $isSalable): IsProductSalableResultInterface
    {
        $result = $this->createStub(IsProductSalableResultInterface::class);
        $result->method('isSalable')->willReturn($isSalable);

        return $result;
    }
}
