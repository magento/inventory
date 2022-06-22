<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Test\Unit\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\InventoryCache\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync\CacheFlush;
use Magento\InventoryCache\Model\FlushCacheByCategoryIds;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CacheFlushTest extends TestCase
{
    /**
     * @var CacheFlush
     */
    private $cacheFlush;

    /**
     * @var FlushCacheByProductIds|MockObject
     */
    private $flushCacheByIds;

    /**
     * @var GetCategoryIdsByProductIds|MockObject
     */
    private $getCategoryIdsByProductIds;

    /**
     * @var FlushCacheByCategoryIds|MockObject
     */
    private $flushCategoryByCategoryIds;

    /**
     * @var GetSalableStatuses|MockObject
     */
    private $getSalableStatuses;

    /**
     * @var GetProductsIdsToProcess|MockObject
     */
    private $getProductsIdsToProcess;

    /**
     * @var Sync|MockObject
     */
    private $sync;

    /**
     * @var \Callable|MockObject
     */
    private $proceedMock;

    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistry;

    /**
     * @var IndexerInterface|MockObject
     */
    private $indexer;

    /**
     * @var bool
     */
    private $isProceedMockCalled = false;

    protected function setUp(): void
    {
        $this->proceedMock = function () {
            $this->isProceedMockCalled = true;
        };
        $this->flushCacheByIds = $this->getMockBuilder(FlushCacheByProductIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getCategoryIdsByProductIds = $this->getMockBuilder(GetCategoryIdsByProductIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flushCategoryByCategoryIds = $this->getMockBuilder(FlushCacheByCategoryIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getSalableStatuses = $this->getMockBuilder(GetSalableStatuses::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getProductsIdsToProcess = $this->getMockBuilder(GetProductsIdsToProcess::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sync = $this->getMockBuilder(Sync::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexer = $this->getMockBuilder(IndexerInterface::class)
            ->getMockForAbstractClass();
        $this->indexerRegistry = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheFlush = new CacheFlush(
            $this->flushCacheByIds,
            $this->getCategoryIdsByProductIds,
            $this->flushCategoryByCategoryIds,
            $this->getSalableStatuses,
            $this->getProductsIdsToProcess,
            $this->indexerRegistry
        );
    }

    /**
     * @dataProvider executeListDataProvider
     * @param array $sourceItemIds
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberOfCacheCleans,
     * @return void
     */
    public function testAroundExecuteList(
        array $sourceItemIds,
        array $beforeSalableList,
        array $afterSalableList,
        array $changedProductIds,
        int $numberOfCacheCleans
    ): void {
        $this->indexerRegistry->expects($this->once())
            ->method('get')
            ->willReturn($this->indexer);
        $this->indexer->expects($this->any())
            ->method('isScheduled')
            ->willReturn(true);
        $this->getSalableStatuses->expects($this->exactly(2))
            ->method('execute')
            ->with($sourceItemIds)
            ->willReturnOnConsecutiveCalls(
                $beforeSalableList,
                $afterSalableList
            );
        $this->getProductsIdsToProcess->expects($this->once())
            ->method('execute')
            ->with($beforeSalableList, $afterSalableList)
            ->willReturn($changedProductIds);

        $this->getCategoryIdsByProductIds->expects($this->exactly($numberOfCacheCleans))
            ->method('execute')
            ->with($changedProductIds)
            ->willReturn([]);
        $this->flushCacheByIds->expects($this->exactly($numberOfCacheCleans))
            ->method('execute')
            ->with($changedProductIds);
        $this->flushCategoryByCategoryIds->expects($this->exactly($numberOfCacheCleans))
            ->method('execute');

        $this->cacheFlush->aroundExecuteList($this->sync, $this->proceedMock, $sourceItemIds);
        $this->assertTrue($this->isProceedMockCalled);
    }

    /**
     * Data provider for testAroundExecuteList
     * @return array
     */
    public function executeListDataProvider(): array
    {
        return [
            [[1], ['sku1' => [1 => true]], ['sku1' => [1 => true]], [], 0],
            [[1], ['sku1' => [1 => true]], ['sku1' => [1 => false]], [1], 1]
        ];
    }
}
