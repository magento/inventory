<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer;

use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\CompositeProductTypesProviderInterface;
use Magento\InventoryCatalogApi\Model\GetChildrenSkusOfParentSkusInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryIndexer\Indexer\CompositeProductsIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeProductsIndexerTest extends TestCase
{
    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkusMock;

    /**
     * @var GetChildrenSkusOfParentSkusInterface|MockObject
     */
    private $getChildrenSkusOfParentSkusMock;

    /**
     * @var GetStockIdsBySkusInterface|MockObject
     */
    private $getStockIdsBySkusMock;

    /**
     * @var SkuListInStockFactory|MockObject
     */
    private $skuListInStockFactoryMock;

    /**
     * @var SkuListsProcessor|MockObject
     */
    private $skuListsProcessorMock;

    /**
     * @var CompositeProductsIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $compositeProductTypesProviderMock = $this->createMock(CompositeProductTypesProviderInterface::class);
        $this->getProductTypesBySkusMock = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->getChildrenSkusOfParentSkusMock = $this->createMock(GetChildrenSkusOfParentSkusInterface::class);
        $this->getStockIdsBySkusMock = $this->createMock(GetStockIdsBySkusInterface::class);
        $this->skuListInStockFactoryMock = $this->createMock(SkuListInStockFactory::class);
        $this->skuListsProcessorMock = $this->createMock(SkuListsProcessor::class);

        $compositeProductTypesProviderMock->method('execute')
            ->willReturn(['bundle', 'configurable', 'grouped']);

        $this->indexer = new CompositeProductsIndexer(
            $compositeProductTypesProviderMock,
            $this->getProductTypesBySkusMock,
            $this->getChildrenSkusOfParentSkusMock,
            $this->getStockIdsBySkusMock,
            $this->skuListInStockFactoryMock,
            $this->skuListsProcessorMock,
        );
    }

    public function testReindexListEmptyList(): void
    {
        $this->getProductTypesBySkusMock->expects(self::never())->method('execute');
        $this->skuListsProcessorMock->expects(self::never())->method('reindexList');
        $this->indexer->reindexList([]);
    }

    public function testReindexListNoCompositePresent(): void
    {
        $skus = ['simple-1', 'simple-2'];

        $this->getProductTypesBySkusMock->expects(self::once())->method('execute')->with($skus)
            ->willReturn([
                'simple-1' => 'simple',
                'simple-2' => 'simple',
            ]);
        $this->getChildrenSkusOfParentSkusMock->expects(self::never())->method('execute');
        $this->skuListsProcessorMock->expects(self::never())->method('reindexList');

        $this->indexer->reindexList($skus);
    }

    public function testReindexList(): void
    {
        $skus = ['bundle-1', 'simple-2', 'configurable-3', 'grouped-4'];

        $this->getProductTypesBySkusMock->expects(self::once())->method('execute')->with($skus)
            ->willReturn([
                'bundle-1' => 'bundle',
                'simple-2' => 'simple',
                'configurable-3' => 'configurable',
                'grouped-4' => 'grouped',
            ]);
        $this->getChildrenSkusOfParentSkusMock->expects(self::once())->method('execute')
            ->with(['bundle-1', 'configurable-3', 'grouped-4'])
            ->willReturn([
                'bundle1' => [],
                'configurable-3' => ['configurable1-red', 'configurable1-green'],
                'grouped-4' => ['simple1'],
            ]);
        $this->getStockIdsBySkusMock->expects(self::exactly(2))->method('execute')
            ->willReturnMap([
                [['configurable1-red', 'configurable1-green'], [2]],
                [['simple1'], [2,3]],
            ]);

        $stock2Mock = $this->createMock(SkuListInStock::class);
        $stock3Mock = $this->createMock(SkuListInStock::class);
        $this->skuListInStockFactoryMock->expects(self::exactly(2))->method('create')
            ->willReturnMap([
                [['stockId' => 2, 'skuList' => ['configurable-3', 'grouped-4']], $stock2Mock],
                [['stockId' => 3, 'skuList' => ['grouped-4']], $stock3Mock],
            ]);
        $this->skuListsProcessorMock->expects(self::once())->method('reindexList')
            ->with([$stock2Mock, $stock3Mock]);

        $this->indexer->reindexList($skus);
    }
}
