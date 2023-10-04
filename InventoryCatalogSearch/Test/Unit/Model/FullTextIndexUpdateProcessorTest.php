<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Test\Unit\Model;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\InventoryCatalogSearch\Model\FullTextIndexUpdateProcessor;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FullTextIndexUpdateProcessorTest extends TestCase
{
    /**
     * @var GetProductsIdsToProcess|MockObject
     */
    private $getProductsIdsToProcess;

    /**
     * @var Processor|MockObject
     */
    private $fulltextUpdateProcessor;

    /**
     * @var FullTextIndexUpdateProcessor
     */
    private $fullTextIndexUpdateProcessor;

    protected function setUp(): void
    {
        $this->getProductsIdsToProcess = $this->getMockBuilder(GetProductsIdsToProcess::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fulltextUpdateProcessor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fullTextIndexUpdateProcessor = new FullTextIndexUpdateProcessor(
            $this->fulltextUpdateProcessor,
            $this->getProductsIdsToProcess
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param array $sourceItemIds
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberOfIndexUpdates,
     * @return void
     */
    public function testAroundExecuteList(
        array $sourceItemIds,
        array $beforeSalableList,
        array $afterSalableList,
        array $changedProductIds,
        int $numberOfIndexUpdates
    ): void {
        $this->getProductsIdsToProcess->expects($this->once())
            ->method('execute')
            ->with($beforeSalableList, $afterSalableList)
            ->willReturn($changedProductIds);
        $this->fulltextUpdateProcessor->expects($this->exactly($numberOfIndexUpdates))
            ->method('reindexList')
            ->with($changedProductIds, true);
        $this->fullTextIndexUpdateProcessor->process($sourceItemIds, $beforeSalableList, $afterSalableList);
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
