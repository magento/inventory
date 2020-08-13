<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\DefaultStockProcessor;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UpdateIndexSalabilityStatus
 */
class UpdateIndexSalabilityStatusTest extends TestCase
{
    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private $defaultStockProvider;
    /**
     * @var IndexProcessor|MockObject
     */
    private $indexProcessor;
    /**
     * @var DefaultStockProcessor|MockObject
     */
    private $defaultStockProcessor;
    /**
     * @var UpdateIndexSalabilityStatus
     */
    private $model;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $this->defaultStockProvider->method('getId')
            ->willReturn(1);
        $this->indexProcessor = $this->createMock(IndexProcessor::class);
        $this->defaultStockProcessor = $this->createMock(DefaultStockProcessor::class);
        $this->model = new UpdateIndexSalabilityStatus(
            $this->defaultStockProvider,
            $this->indexProcessor,
            $this->defaultStockProcessor
        );
    }

    /**
     * Test that default stock indexer is executed if the stock is default otherwise custom stock indexer is executed
     *
     * @param int $stockId
     * @param int $defaultProcessorInvokeCount
     * @param int $indexProcessorInvokeCount
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        int $stockId,
        int $defaultProcessorInvokeCount,
        int $indexProcessorInvokeCount
    ): void {
        $skus = ['P1', 'P2'];
        $changes = ['P1' => false];
        $reservation = new ReservationData($skus, $stockId);
        $this->defaultStockProcessor->expects($this->exactly($defaultProcessorInvokeCount))
            ->method('execute')
            ->with($reservation)
            ->willReturn($changes);
        $this->indexProcessor->expects($this->exactly($indexProcessorInvokeCount))
            ->method('execute')
            ->with($reservation, $stockId)
            ->willReturn($changes);
        $this->assertEquals($changes, $this->model->execute($reservation));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [1, 1, 0],
            [2, 0, 1],
            [3, 0, 1]
        ];
    }
}
