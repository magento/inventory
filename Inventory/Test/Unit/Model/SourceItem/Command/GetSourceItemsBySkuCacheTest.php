<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model\SourceItem\Command;

use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku;
use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySkuCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetSourceItemsBySkuCacheTest extends TestCase
{
    /**
     * @var GetSourceItemsBySku|MockObject
     */
    private $getSourceItemsBySkuMock;

    /**
     * @var GetSourceItemsBySkuCache
     */
    private $getSourceItemsBySkuCache;

    protected function setUp(): void
    {
        $this->getSourceItemsBySkuMock = $this->createMock(GetSourceItemsBySku::class);
        $this->getSourceItemsBySkuCache = new GetSourceItemsBySkuCache($this->getSourceItemsBySkuMock);
    }

    public function testExecute(): void
    {
        $sku = 'product1';
        $this->getSourceItemsBySkuMock->expects(self::once())
            ->method('execute')
            ->with($sku);
        $this->getSourceItemsBySkuCache->execute($sku);
        $this->getSourceItemsBySkuCache->execute($sku);
    }
}
