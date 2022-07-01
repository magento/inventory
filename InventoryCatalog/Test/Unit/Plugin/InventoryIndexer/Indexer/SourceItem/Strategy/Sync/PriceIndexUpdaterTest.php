<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryCatalog\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync\PriceIndexUpdater;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Model\ResourceModel\GetSourceCodesBySourceItemIds;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PriceIndexUpdaterTest extends TestCase
{
    /**
     * @var Processor|MockObject
     */
    private $priceIndexProcessor;

    /**
     * @var GetSourceCodesBySourceItemIds|MockObject
     */
    private $getSourceCodesBySourceItemIds;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private $defaultSourceProvider;

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
     * @var PriceIndexUpdater
     */
    private $priceIndexUpdater;

    /**
     * @var bool
     */
    private $isProceedMockCalled = false;

    /**
     * @var \Callable|MockObject
     */
    private $proceedMock;

    protected function setUp(): void
    {
        $this->proceedMock = function () {
            $this->isProceedMockCalled = true;
        };

        $this->priceIndexProcessor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getSourceCodesBySourceItemIds = $this->getMockBuilder(GetSourceCodesBySourceItemIds::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->defaultSourceProvider = $this->getMockBuilder(DefaultSourceProviderInterface::class)
            ->getMockForAbstractClass();
        $this->getSalableStatuses = $this->getMockBuilder(GetSalableStatuses::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getProductsIdsToProcess = $this->getMockBuilder(GetProductsIdsToProcess::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sync = $this->getMockBuilder(Sync::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceIndexUpdater = new priceIndexUpdater(
            $this->priceIndexProcessor,
            $this->getSourceCodesBySourceItemIds,
            $this->defaultSourceProvider,
            $this->getSalableStatuses,
            $this->getProductsIdsToProcess
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
        int $numberReindexCalls
    ): void {
        $this->defaultSourceProvider->expects($this->once())
            ->method('getCode')
            ->willReturn('code');
        $this->getSourceCodesBySourceItemIds->expects($this->once())
            ->method('execute')
            ->with($sourceItemIds)
            ->willReturn($sourceItemIds);
        $this->getSalableStatuses->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                $beforeSalableList,
                $afterSalableList
            );
        $this->getProductsIdsToProcess->expects($this->once())
            ->method('execute')
            ->with($beforeSalableList, $afterSalableList)
            ->willReturn($changedProductIds);
        $this->priceIndexProcessor->expects($this->exactly($numberReindexCalls))
            ->method('reindexList')
            ->with($changedProductIds, true);

        $this->priceIndexUpdater->aroundExecuteList($this->sync, $this->proceedMock, $sourceItemIds);
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
