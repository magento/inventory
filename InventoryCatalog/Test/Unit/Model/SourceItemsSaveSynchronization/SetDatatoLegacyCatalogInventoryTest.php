<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model\SourceItemsSaveSynchronization;

use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\CacheCleaner;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization\SetDataToLegacyCatalogInventory;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetDatatoLegacyCatalogInventoryTest extends TestCase
{
    /**
     * @var SetDataToLegacyCatalogInventory
     */
    private $model;

    /**
     * @var SetDataToLegacyStockItem|MockObject
     */
    private $setDataToLegacyStockItem;

    /**
     * @var SetDataToLegacyStockStatus|MockObject
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var StockItemCriteriaInterfaceFactory|MockObject
     */
    private $legacyStockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface|MockObject
     */
    private $legacyStockItemRepository;

    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private $getProductIdsBySkus;

    /**
     * @var StockStateProviderInterface|MockObject
     */
    private $stockStateProvider;

    /**
     * @var Processor|MockObject
     */
    private $indexerProcessor;

    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalable;

    /**
     * @var \Magento\Inventory\Model\SourceItem|MockObject
     */
    private $sourceItem;

    /**
     * @var CacheCleaner|MockObject
     */
    private CacheCleaner $cacheCleaner;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->setDataToLegacyStockItem = $this->getMockBuilder(SetDataToLegacyStockItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->setDataToLegacyStockStatus = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->legacyStockItemCriteriaFactory = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addFilter'])
            ->getMock();

        $this->legacyStockItemRepository = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getProductIdsBySkus = $this->getMockBuilder(GetProductIdsBySkusInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockStateProvider = $this->getMockBuilder(StockStateProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->indexerProcessor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reindexList'])
            ->getMock();

        $this->areProductsSalable = $this->getMockBuilder(AreProductsSalableInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sourceItem = $this->getMockBuilder(SourceItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSku', 'setSourceCode', 'setQuantity', 'setStatus', 'getSku'])
            ->getMock();

        $this->cacheCleaner = $this->getMockBuilder(CacheCleaner::class)
            ->disableOriginalConstructor()
            ->setMethods(['clean'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            SetDataToLegacyCatalogInventory::class,
            [
                'setDataToLegacyStockItem' => $this->setDataToLegacyStockItem,
                'legacyStockItemCriteriaFactory' => $this->legacyStockItemCriteriaFactory,
                'legacyStockItemRepository' => $this->legacyStockItemRepository,
                'getProductIdsBySkus' => $this->getProductIdsBySkus,
                'stockStateProvider' => $this->stockStateProvider,
                'indexerProcessor' => $this->indexerProcessor,
                'setDataToLegacyStockStatus' => $this->setDataToLegacyStockStatus,
                'areProductsSalable' => $this->areProductsSalable,
                'cacheCleaner' => $this->cacheCleaner
            ]
        );
    }

    /**
     * @dataProvider getDataProvider
     * @return void
     */
    public function testExecute($productId, $sku, $quantity, $stock_status): void
    {
        $skus = [$sku];

        $this->sourceItem->setSku($sku);
        $this->sourceItem->setSourceCode('default');
        $this->sourceItem->setQuantity($quantity);
        $this->sourceItem->setStatus($stock_status);

        $this->sourceItem->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn($sku);

        $this->getProductIdsBySkus->expects($this->atLeastOnce())
            ->method('execute')
            ->with($skus)
            ->willReturn([$sku => $productId]);

        $this->areProductsSalable->expects($this->atLeastOnce())
            ->method('execute')
            ->with($skus)
            ->willReturn([$sku => (bool)$stock_status]);
        $this->assertEquals([$sku => $stock_status], $this->areProductsSalable->execute($skus, 1));

        $callback = function () {
        };
        $this->cacheCleaner->expects($this->atLeastOnce())
            ->method('clean')
            ->with([$productId], $callback)
            ->willReturnCallback( function () {
                $this->sourceItem;
            });

        $this->model->execute([$this->sourceItem]);
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [201, 'COC002-Red-M', 1, 1],
            [202, 'COC002-Black-M', 1, 0],
        ];
    }
}
