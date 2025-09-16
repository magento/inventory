<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer\Stock;

use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryIndexer\Indexer\Stock\GetSalableStatuses;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetSalableStatusesTest extends TestCase
{
    /**
     * @var GetSalableStatuses
     */
    private $model;

    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalableMock;

    public function setUp(): void
    {
        $this->areProductsSalableMock = $this->createMock(AreProductsSalableInterface::class);
        $this->model = new GetSalableStatuses($this->areProductsSalableMock);
    }

    #[
        DataProvider('executeDataProvider'),
    ]
    public function testExecute(array $skusPerStock, array $expected): void
    {
        $skuInStockList = [];
        $isSalableReturnMap = [];
        foreach ($skusPerStock as $stockId => $skus) {
            $skuInStock = $this->createMock(SkuListInStock::class);
            $skuInStock->method('getStockId')->willReturn($stockId);
            $skuInStock->method('getSkuList')->willReturn(array_keys($skus));
            $skuInStockList[] = $skuInStock;

            $areProductsSalable = [];
            foreach ($skus as $sku => $isSalable) {
                $isProductSalable = $this->createMock(IsProductSalableResultInterface::class);
                $isProductSalable->method('getStockId')->willReturn($stockId);
                $isProductSalable->method('getSku')->willReturn($sku);
                $isProductSalable->method('isSalable')->willReturn($isSalable);
                $areProductsSalable[] = $isProductSalable;
            }
            $isSalableReturnMap[] = [array_keys($skus), $stockId, $areProductsSalable];
        }

        $this->areProductsSalableMock->method('execute')
            ->willReturnMap($isSalableReturnMap);

        $result = $this->model->execute($skuInStockList);
        $this->assertSameSize($expected, $result);
        $this->assertSame($expected, $result);
    }

    public static function executeDataProvider(): array
    {
        return [
            [
                [
                    2 => ['sku1' => true, 'sku2' => false],
                ],
                [
                    'sku1' => [2 => true],
                    'sku2' => [2 => false],
                ],
            ],
            [
                [
                    2 => ['sku1' => false],
                    3 => ['sku1' => true],
                ],
                [
                    'sku1' => [2 => false, 3 => true],
                ],
            ],
            [
                [
                    2 => ['sku1' => true, 'sku2' => true],
                    3 => ['sku3' => false, 'sku4' => false],
                    4 => ['sku5' => true],
                    5 => ['sku2' => false, 'sku3' => false],
                ],
                [

                    'sku1' => [2 => true],
                    'sku2' => [2 => true, 5 => false],
                    'sku3' => [3 => false, 5 => false],
                    'sku4' => [3 => false],
                    'sku5' => [4 => true],
                ],
            ],
        ];
    }
}
