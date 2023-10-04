<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Test\Unit\Model;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryCache\Model\CacheFlushProcessor;
use Magento\InventoryCache\Model\FlushCacheByCategoryIds;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheFlushProcessorTest extends TestCase
{
    /**
     * @var CacheFlush
     */
    private $cacheFlushProcessor;

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
     * @var GetProductsIdsToProcess|MockObject
     */
    private $getProductsIdsToProcess;

    /**
     * @var IndexerRegistry|MockObject
     */
    private $indexerRegistry;

    /**
     * @var IndexerInterface|MockObject
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->flushCacheByIds = $this->getMockBuilder(FlushCacheByProductIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getCategoryIdsByProductIds = $this->getMockBuilder(GetCategoryIdsByProductIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flushCategoryByCategoryIds = $this->getMockBuilder(FlushCacheByCategoryIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getProductsIdsToProcess = $this->getMockBuilder(GetProductsIdsToProcess::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexer = $this->getMockBuilder(IndexerInterface::class)
            ->getMockForAbstractClass();
        $this->indexerRegistry = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheFlushProcessor = new CacheFlushProcessor(
            $this->flushCacheByIds,
            $this->getCategoryIdsByProductIds,
            $this->flushCategoryByCategoryIds,
            $this->getProductsIdsToProcess,
            $this->indexerRegistry
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param array $sourceItemIds
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberOfCacheCleans,
     * @return void
     */
    public function testProcess(
        array $sourceItemIds,
        array $beforeSalableList,
        array $afterSalableList,
        array $changedProductIds,
        int $numberOfCacheCleans
    ): void {
        $this->indexerRegistry->expects($this->once())
            ->method('get')
            ->willReturn($this->indexer);
        $this->indexer->expects($this->once())
            ->method('isScheduled')
            ->willReturn(true);
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

        $this->cacheFlushProcessor->process($sourceItemIds, $beforeSalableList, $afterSalableList);
    }

    /**
     * @return array
     */
    public function processDataProvider(): array
    {
        return [
            [[1], ['sku1' => [1 => true]], ['sku1' => [1 => true]], [], 0],
            [[1], ['sku1' => [1 => true]], ['sku1' => [1 => false]], [1], 1]
        ];
    }
}
