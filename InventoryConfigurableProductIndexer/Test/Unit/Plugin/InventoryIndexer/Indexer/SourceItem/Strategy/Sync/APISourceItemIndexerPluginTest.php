<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

// @codingStandardsIgnoreStart
namespace Magento\InventoryConfigurableProductIndexer\Test\Unit\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync\APISourceItemIndexerPlugin;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
// @codingStandardsIgnoreEnd

class APISourceItemIndexerPluginTest extends TestCase
{
    /**
     * @var APISourceItemIndexerPlugin
     */
    private $plugin;

    /**
     * @var Configurable|MockObject
     */
    private $typeInstanceMock;

    /**
     * @var GetSkusByProductIdsInterface|MockObject
     */
    private $getSkusByProductIdsMock;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeInstanceMock = $this->createMock(Configurable::class);
        $this->getSkusByProductIdsMock = $this->createMock(GetSkusByProductIdsInterface::class);
        $this->getStockIdsBySkusMock = $this->createMock(GetStockIdsBySkusInterface::class);
        $this->skuListInStockFactoryMock = $this->createMock(SkuListInStockFactory::class);
        $this->skuListsProcessorMock = $this->createMock(SkuListsProcessor::class);
        $this->plugin = new APISourceItemIndexerPlugin(
            $this->typeInstanceMock,
            $this->getSkusByProductIdsMock,
            $this->getStockIdsBySkusMock,
            $this->skuListInStockFactoryMock,
            $this->skuListsProcessorMock,
        );
    }

    public function testAfterSave()
    {
        $confId = 1;
        $confSku = 'configurable1';
        $stockId = 2;
        $childIds = [11, 12];
        $childSkus = ['sku-11', 'sku-12'];

        $subject = $this->createMock(ProductResource::class);
        $result = $this->createMock(ProductResource::class);
        $object = $this->createMock(Product::class);
        $object->expects($this->once())->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $this->typeInstanceMock->expects($this->once())
            ->method('getChildrenIds')
            ->with($confId)
            ->willReturn([$childIds]);

        $this->getSkusByProductIdsMock->expects($this->once())
            ->method('execute')
            ->with($childIds)
            ->willReturn($childSkus);
        $this->getStockIdsBySkusMock->expects($this->once())->method('execute')->with($childSkus)->willReturn([2]);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);
        $this->skuListInStockFactoryMock->expects($this->once())
            ->method('create')
            ->with(['stockId' => $stockId, 'skuList' => [$confSku]])
            ->willReturn($skuListInStockMock);
        $this->skuListsProcessorMock->expects($this->once())->method('reindexList')->with([$skuListInStockMock]);

        $object->expects($this->once())->method('getId')->willReturn($confId);
        $object->expects($this->once())->method('getSku')->willReturn($confSku);
        $object->expects($this->once())->method('cleanModelCache');

        $interceptorResult = $this->plugin->afterSave($subject, $result, $object);
        $this->assertSame($interceptorResult, $result);
    }
}
