<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\CatalogInventory\Model\Stock\StockItemRepository;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Full as FullProductIndexer;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryCatalog\Plugin\CatalogInventory\Model\Stock\StockItemRepository\StockItemRepositoryPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockItemRepositoryPluginTest extends TestCase
{
    /** @var FullProductIndexer|MockObject */
    private $fullProductIndexer;

    /** @var InventoryIndexer|MockObject */
    private $inventoryIndexer;

    /** @var ProductRepositoryInterface|MockObject */
    private $productRepository;

    /** @var GetSourceItemsBySku|MockObject */
    private $getSourceItemsBySku;

    /** @var StockItemRepositoryPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->fullProductIndexer = $this->createMock(FullProductIndexer::class);
        $this->inventoryIndexer = $this->createMock(InventoryIndexer::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->getSourceItemsBySku = $this->createMock(GetSourceItemsBySku::class);

        $this->plugin = new StockItemRepositoryPlugin(
            $this->fullProductIndexer,
            $this->inventoryIndexer,
            $this->productRepository,
            $this->getSourceItemsBySku
        );
    }

    public function testAfterSave(): void
    {
        $productId = 123;
        $sku = 'test-sku';
        $sourceItemId = 456;

        $stockItem = $this->createMock(StockItemInterface::class);
        $stockItem->method('getProductId')->willReturn($productId);

        $product = $this->createMock(\Magento\Catalog\Api\Data\ProductInterface::class);
        $product->method('getId')->willReturn($productId);
        $product->method('getSku')->willReturn($sku);
        $this->productRepository->method('getById')->with($productId)->willReturn($product);

        $sourceItem = $this->createMock(SourceItem::class);
        $sourceItem->method('getId')->willReturn($sourceItemId);
        $this->getSourceItemsBySku->method('execute')->with($sku)->willReturn([$sourceItem]);

        $this->fullProductIndexer->expects($this->once())->method('executeRow')->with($productId);
        $this->inventoryIndexer->expects($this->once())->method('executeList')->with([$sourceItemId]);

        $result = $this->plugin->afterSave(
            $this->createMock(StockItemRepository::class),
            $stockItem
        );

        $this->assertSame($stockItem, $result);
    }
}
