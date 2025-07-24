<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
        $this->flushCacheByIds = $this->createMock(FlushCacheByProductIds::class);
        $this->getCategoryIdsByProductIds = $this->createMock(GetCategoryIdsByProductIds::class);
        $this->flushCategoryByCategoryIds = $this->createMock(FlushCacheByCategoryIds::class);
        $this->getProductsIdsToProcess = $this->createMock(GetProductsIdsToProcess::class);
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexerRegistry = $this->createMock(IndexerRegistry::class);

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
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberOfCacheCleans,
     * @return void
     */
    public function testProcess(
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

        $this->cacheFlushProcessor->process($beforeSalableList, $afterSalableList);
    }

    /**
     * @return array
     */
    public static function processDataProvider(): array
    {
        return [
            [['sku1' => [1 => true]], ['sku1' => [1 => true]], [], 0],
            [['sku1' => [1 => true]], ['sku1' => [1 => false]], [1], 1]
        ];
    }
}
