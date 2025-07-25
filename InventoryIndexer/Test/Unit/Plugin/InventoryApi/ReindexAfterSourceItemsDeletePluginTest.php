<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Magento\InventoryIndexer\Plugin\InventoryApi\ReindexAfterSourceItemsDeletePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexAfterSourceItemsDeletePluginTest extends TestCase
{
    /**
     * @var ReindexAfterSourceItemsDeletePlugin
     */
    private $plugin;

    /**
     * @var GetSourceItemIds|MockObject
     */
    private $getSourceItemIdsMock;

    /**
     * @var GetSkuListInStock|MockObject
     */
    private $getSkuListInStockMock;

    /**
     * @var SkuListsProcessor|MockObject
     */
    private $skuListsProcessor;

    protected function setUp(): void
    {
        $this->getSourceItemIdsMock = $this->createMock(GetSourceItemIds::class);
        $this->getSkuListInStockMock = $this->createMock(GetSkuListInStock::class);
        $this->skuListsProcessor = $this->createMock(SkuListsProcessor::class);
        $this->plugin = new ReindexAfterSourceItemsDeletePlugin(
            $this->getSourceItemIdsMock,
            $this->getSkuListInStockMock,
            $this->skuListsProcessor,
        );
    }

    public function testAroundExecute(): void
    {
        $sourceIds = [2];

        $sourceItemMock = $this->createMock(SourceItemInterface::class);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);

        $subjectMock = $this->createMock(SourceItemsDeleteInterface::class);
        $proceedMock = $this->getMockBuilder(\stdclass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $proceedMock->expects($this->once())->method('__invoke');

        $this->getSourceItemIdsMock->expects($this->once())
            ->method('execute')
            ->with([$sourceItemMock])
            ->willReturn($sourceIds);
        $this->getSkuListInStockMock->expects($this->once())
            ->method('execute')
            ->with($sourceIds)
            ->willReturn([$skuListInStockMock]);
        $this->skuListsProcessor->expects($this->once())
            ->method('reindexList')
            ->with([$skuListInStockMock]);

        $this->plugin->aroundExecute($subjectMock, $proceedMock, [$sourceItemMock]);
    }
}
