<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGraphQl\Test\Unit\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryGraphQl\Model\Resolver\OnlyXLeftInStockResolver;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OnlyXLeftInStockResolverTest extends TestCase
{
    /** @var GetProductSalableQtyInterface&MockObject */
    private $getProductSalableQty;

    /** @var GetStockIdForCurrentWebsite&MockObject */
    private $getStockIdForCurrentWebsite;

    /** @var GetStockItemConfigurationInterface&MockObject */
    private $getStockItemConfiguration;

    /** @var StockItemConfigurationInterface&MockObject */
    private $stockItemConfiguration;

    /** @var Field&MockObject */
    private $fieldMock;

    /** @var ResolveInfo&MockObject */
    private $resolveInfoMock;

    /** @var ProductInterface&MockObject */
    private $productMock;

    /** @var ResolverInterface */
    private $resolver;

    protected function setUp(): void
    {
        $this->getProductSalableQty = $this->getMockBuilder(GetProductSalableQtyInterface::class)->getMock();
        $this->getStockIdForCurrentWebsite = $this->getMockBuilder(GetStockIdForCurrentWebsite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getStockItemConfiguration = $this->getMockBuilder(GetStockItemConfigurationInterface::class)->getMock();
        $this->stockItemConfiguration = $this->getMockBuilder(StockItemConfigurationInterface::class)->getMock();

        $this->fieldMock = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock();
        $this->productMock = $this->getMockBuilder(ProductInterface::class)->getMock();

        $this->resolver = new OnlyXLeftInStockResolver(
            $this->getProductSalableQty,
            $this->getStockIdForCurrentWebsite,
            $this->getStockItemConfiguration
        );
    }

    public function testResolveReturnsQtyWhenBelowThresholdNonBundle(): void
    {
        $sku = 'SKU-1';
        $stockId = 1;
        $threshold = 5.0;
        $salableQty = 3.0;

        $this->productMock->method('getTypeId')->willReturn('simple');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($salableQty);

        $result = $this->resolver
            ->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
        $this->assertSame($salableQty, $result);
    }

    public function testResolveReturnsNullWhenAboveThreshold(): void
    {
        $sku = 'SKU-2';
        $stockId = 2;
        $threshold = 2.0;
        $salableQty = 5.0;

        $this->productMock->method('getTypeId')->willReturn('virtual');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($salableQty);

        $result = $this->resolver
            ->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
        $this->assertNull($result);
    }

    public function testResolveReturnsNullWhenQtyZero(): void
    {
        $sku = 'SKU-3';
        $stockId = 3;
        $threshold = 10.0;
        $salableQty = 0.0;

        $this->productMock->method('getTypeId')->willReturn('downloadable');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($salableQty);

        $result = $this->resolver
            ->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
        $this->assertNull($result);
    }

    public function testResolveReturnsNullWhenThresholdZero(): void
    {
        $sku = 'SKU-4';
        $stockId = 4;
        $threshold = 0.0;

        $this->productMock->method('getTypeId')->willReturn('simple');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn(1.0);

        $result = $this->resolver
            ->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
        $this->assertNull($result);
    }

    public function testResolveUsesValueSkuForBundle(): void
    {
        $bundleSku = 'BUNDLE-SKU';
        $childSku = 'CHILD-SKU';
        $stockId = 5;
        $threshold = 5.0;
        $salableQty = 1.0;

        $this->productMock->method('getTypeId')->willReturn('bundle');
        $this->productMock->method('getSku')->willReturn($bundleSku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($childSku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($childSku, $stockId)
            ->willReturn($salableQty);

        $result = $this->resolver->resolve(
            $this->fieldMock,
            null,
            $this->resolveInfoMock,
            ['model' => $this->productMock, 'sku' => $childSku]
        );
        $this->assertSame($salableQty, $result);
    }

    public function testResolveReturnsNullOnLocalizedExceptionFromQty(): void
    {
        $sku = 'SKU-5';
        $stockId = 6;
        $threshold = 3.0;

        $this->productMock->method('getTypeId')->willReturn('simple');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willReturn($this->stockItemConfiguration);
        $this->stockItemConfiguration->expects($this->once())->method('getStockThresholdQty')->willReturn($threshold);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willThrowException(new LocalizedException(new Phrase('error')));

        $result = $this->resolver
            ->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
        $this->assertNull($result);
    }

    public function testResolvePropagatesSkuNotAssignedException(): void
    {
        $sku = 'SKU-6';
        $stockId = 7;

        $this->productMock->method('getTypeId')->willReturn('simple');
        $this->productMock->method('getSku')->willReturn($sku);

        $this->getStockIdForCurrentWebsite->expects($this->once())->method('execute')->willReturn($stockId);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with($sku, $stockId)
            ->willThrowException(new SkuIsNotAssignedToStockException(new Phrase('error')));

        $this->expectException(SkuIsNotAssignedToStockException::class);
        $this->resolver->resolve($this->fieldMock, null, $this->resolveInfoMock, ['model' => $this->productMock]);
    }
}
