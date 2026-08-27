<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Test\Unit\Plugin\InventorySales\Model\IsProductSalableCondition;

use Magento\Bundle\Model\Product\Type;
use Magento\InventoryBundleProduct\Model\IsBundleProductChildrenSalable;
// phpcs:disable
use Magento\InventoryBundleProduct\Plugin\InventorySales\Model\IsProductSalableCondition\GetIsQtySalableForBundleProduct;
// phpcs:enable
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySalesApi\Model\GetIsQtySalableInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetIsQtySalableForBundleProductTest extends TestCase
{
    /**
     * @var IsBundleProductChildrenSalable|MockObject
     */
    private $isBundleProductChildrenSalable;

    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkus;

    /**
     * @var GetIsQtySalableInterface|MockObject
     */
    private $subject;

    /**
     * @var GetIsQtySalableForBundleProduct
     */
    private $model;

    protected function setUp(): void
    {
        $this->isBundleProductChildrenSalable = $this->createMock(IsBundleProductChildrenSalable::class);
        $this->getProductTypesBySkus = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->subject = $this->createMock(GetIsQtySalableInterface::class);

        $this->model = new GetIsQtySalableForBundleProduct(
            $this->isBundleProductChildrenSalable,
            $this->getProductTypesBySkus
        );
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @param string $type
     * @param bool $isSalable
     * @param int $num
     * @param bool $expected
     * @return void
     */
    #[DataProvider('salabilityDataProvider')]
    public function testAfterExecute(
        string $sku,
        int $stockId,
        string $type,
        bool $isSalable,
        int $num,
        bool $expected
    ): void {
        $this->getProductTypesBySkus->expects($this->once())
            ->method('execute')
            ->with([$sku])
            ->willReturn([$sku => $type]);
        $this->isBundleProductChildrenSalable->expects($this->exactly($num))
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn(true);
        $this->assertSame(
            $expected,
            $this->model->afterExecute($this->subject, $isSalable, $sku, $stockId)
        );
    }

    public static function salabilityDataProvider() : array
    {
        return [
            [
                'sku' => 'bundle-test',
                'stockId' => 1,
                'type' => Type::TYPE_CODE,
                'isSalable' => false,
                'num' => 1,
                'expected' => true
            ],
            [
                'sku' => 'bundle-test',
                'stockId' => 1,
                'type' => Type::TYPE_CODE,
                'isSalable' => true,
                'num' => 1,
                'expected' => true
            ],
            [
                'sku' => 'simple',
                'stockId' => 1,
                'type' => 'simple',
                'isSalable' => false,
                'num' => 0,
                'expected' => false
            ],
            [
                'sku' => 'simple',
                'stockId' => 1,
                'type' => 'simple',
                'isSalable' => true,
                'num' => 0,
                'expected' => true
            ],
        ];
    }
}
