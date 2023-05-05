<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Test\Unit\Plugin\Api;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ProductRepository;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryCache\Plugin\Api\CacheFlush;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheFlushTest extends TestCase
{
    /**
     * @var FlushCacheByProductIds|MockObject
     */
    private FlushCacheByProductIds $flushCacheByIds;

    /**
     * @var CacheFlush
     */
    private CacheFlush $cacheFlush;

    protected function setUp(): void
    {
        $this->flushCacheByIds = $this->createMock(FlushCacheByProductIds::class);
        $this->cacheFlush = new CacheFlush($this->flushCacheByIds);

        parent::setUp();
    }

    public function testAfterSave(): void
    {
        $productId = 1;
        $childId = 2;

        $result = $this->createMock(Product::class);
        $result->expects($this->exactly(2))->method('getId')->willReturn($productId);
        $typeInstance = $this->createMock(AbstractType::class);
        $typeInstance->expects($this->once())
            ->method('getChildrenIds')
            ->with($productId)
            ->willReturn([[$childId => (string)$childId]]);
        $product = $this->createMock(Product::class);
        $result->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $this->flushCacheByIds->expects($this->once())->method('execute')->with([$productId, $childId]);

        $this->cacheFlush->afterSave($this->createMock(ProductRepository::class), $result, $product, false);
    }
}
