<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryCatalog\Model\PriceIndexUpdateProcessor;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceIndexUpdateProcessorTest extends TestCase
{
    /**
     * @var Processor|MockObject
     */
    private $priceIndexProcessor;

    /**
     * @var GetProductsIdsToProcess|MockObject
     */
    private $getProductsIdsToProcess;

    /**
     * @var PriceIndexUpdateProcessor
     */
    private $priceIndexUpdateProcessor;

    protected function setUp(): void
    {
        $this->priceIndexProcessor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getProductsIdsToProcess = $this->getMockBuilder(GetProductsIdsToProcess::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceIndexUpdateProcessor = new PriceIndexUpdateProcessor(
            $this->priceIndexProcessor,
            $this->getProductsIdsToProcess
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param array $sourceItemIds
     * @param array $beforeSalableList
     * @param array $afterSalableList
     * @param array $changedProductIds,
     * @param int $numberReindexCalls,
     * @return void
     */
    public function testProcess(
        array $sourceItemIds,
        array $beforeSalableList,
        array $afterSalableList,
        array $changedProductIds,
        int $numberReindexCalls
    ): void {
        $this->getProductsIdsToProcess->expects($this->once())
            ->method('execute')
            ->with($beforeSalableList, $afterSalableList)
            ->willReturn($changedProductIds);
        $this->priceIndexProcessor->expects($this->exactly($numberReindexCalls))
            ->method('reindexList')
            ->with($changedProductIds, true);

        $this->priceIndexUpdateProcessor->process($sourceItemIds, $beforeSalableList, $afterSalableList);
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
