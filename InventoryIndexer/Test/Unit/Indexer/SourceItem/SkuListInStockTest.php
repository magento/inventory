<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer\SourceItem;

use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(SkuListInStock::class),
]
class SkuListInStockTest extends TestCase
{
    public function testCreate(): void
    {
        $stockId = 2;
        $skuList = ['sku1', 'sku2'];
        $skuListInStock = new SkuListInStock($stockId, $skuList);
        self::assertEquals($stockId, $skuListInStock->getStockId());
        self::assertEquals($skuList, $skuListInStock->getSkuList());
    }
}
