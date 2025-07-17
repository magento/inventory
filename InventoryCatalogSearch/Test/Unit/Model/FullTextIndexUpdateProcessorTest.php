<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
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
        $this->getProductsIdsToProcess = $this->createMock(GetProductsIdsToProcess::class);
        $this->fulltextUpdateProcessor = $this->createMock(Processor::class);

        $this->fullTextIndexUpdateProcessor = new FullTextIndexUpdateProcessor(
            $this->fulltextUpdateProcessor,
            $this->getProductsIdsToProcess
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberOfIndexUpdates,
     * @return void
     */
    public function testAroundExecuteList(
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
        $this->fullTextIndexUpdateProcessor->process($beforeSalableList, $afterSalableList);
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
