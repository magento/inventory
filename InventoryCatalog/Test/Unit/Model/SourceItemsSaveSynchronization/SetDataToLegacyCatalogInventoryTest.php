<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model\SourceItemsSaveSynchronization;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\CacheCleaner;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization\SetDataToLegacyCatalogInventory;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItems;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for legacy catalog inventory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetDataToLegacyCatalogInventoryTest extends TestCase
{
    /**
     * @var SetDataToLegacyCatalogInventory
     */
    private $model;

    /**
     * @var SetDataToLegacyStockItem&MockObject
     */
    private $setDataToLegacyStockItem;

    /**
     * @var SetDataToLegacyStockStatus&MockObject
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var GetLegacyStockItems&MockObject
     */
    private $getLegacyStockItems;

    /**
     * @var GetProductIdsBySkusInterface&MockObject
     */
    private $getProductIdsBySkus;

    /**
     * @var StockStateProviderInterface&MockObject
     */
    private $stockStateProvider;

    /**
     * @var Processor&MockObject
     */
    private $indexerProcessor;

    /**
     * @var AreProductsSalableInterface&MockObject
     */
    private $areProductsSalable;

    /**
     * @var CacheCleaner&MockObject
     */
    private $cacheCleaner;

    protected function setUp(): void
    {
        $this->setDataToLegacyStockItem = $this->getMockBuilder(SetDataToLegacyStockItem::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMock();
        $this->setDataToLegacyStockStatus = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMock();
        $this->getLegacyStockItems = $this->getMockBuilder(GetLegacyStockItems::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMock();
        $this->getProductIdsBySkus = $this->getMockBuilder(GetProductIdsBySkusInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->stockStateProvider = $this->getMockBuilder(StockStateProviderInterface::class)
            ->onlyMethods(['verifyStock'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->indexerProcessor = $this->getMockBuilder(Processor::class)
            ->onlyMethods(['reindexList'])->disableOriginalConstructor()->getMock();
        $this->areProductsSalable = $this->getMockBuilder(AreProductsSalableInterface::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->cacheCleaner = $this->getMockBuilder(CacheCleaner::class)
            ->disableOriginalConstructor()->setMethods(['clean'])->getMock();
        $this->model = (new ObjectManager($this))->getObject(
            SetDataToLegacyCatalogInventory::class,
            [
                'setDataToLegacyStockItem' => $this->setDataToLegacyStockItem,
                'getProductIdsBySkus' => $this->getProductIdsBySkus,
                'stockStateProvider' => $this->stockStateProvider,
                'indexerProcessor' => $this->indexerProcessor,
                'setDataToLegacyStockStatus' => $this->setDataToLegacyStockStatus,
                'areProductsSalable' => $this->areProductsSalable,
                'cacheCleaner' => $this->cacheCleaner,
                'getLegacyStockItems' => $this->getLegacyStockItems,
            ]
        );
    }

    /**
     * @dataProvider getDataProvider
     * @return void
     */
    public function testExecute($productId, $sku, $quantity, $stockStatus): void
    {
        $skus = [$sku];
        $sourceItemMock = $this->getMockBuilder(SourceItem::class)->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getSourceCode', 'getQuantity', 'getStatus', 'getData'])->getMock();
        $sourceItemMock->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $sourceItemMock->expects($this->atLeastOnce())->method('getQuantity')->willReturn((float)$quantity);
        $sourceItemMock->expects($this->atLeastOnce())->method('getStatus')->willReturn($stockStatus);
        $isProductSalableMock = $this->getMockBuilder(IsProductSalableResultInterface::class)
            ->onlyMethods(['getSku', 'isSalable'])->disableOriginalConstructor()->getMockForAbstractClass();
        $isProductSalableMock->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $isProductSalableMock->expects($this->atLeastOnce())->method('isSalable')
            ->willReturn((bool)$stockStatus);
        $this->areProductsSalable->expects($this->atLeastOnce())->method('execute')
            ->with($skus, Stock::DEFAULT_STOCK_ID)->willReturn([$isProductSalableMock]);
        $this->getProductIdsBySkus->expects($this->atLeastOnce())->method('execute')->with($skus)
            ->willReturn([$sku => $productId]);
        $stockItemMock = $this->getMockBuilder(StockItemInterface::class)
            ->onlyMethods(['getManageStock', 'setIsInStock', 'setQty'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $stockItemMock->expects($this->once())->method('getManageStock')->willReturn(true);
        $stockItemMock->expects($this->once())->method('setIsInStock')->willReturn($stockStatus);
        $stockItemMock->expects($this->once())->method('setQty')->willReturn($quantity);
        $stockItemMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $this->getLegacyStockItems->expects($this->once())->method('execute')->willReturn([$stockItemMock]);
        $this->stockStateProvider->expects($this->once())->method('verifyStock')->with($stockItemMock)
            ->willReturn(true);
        $this->setDataToLegacyStockItem->expects($this->once())->method('execute')
            ->with($sku, $quantity, $stockStatus);
        $this->setDataToLegacyStockStatus->expects($this->once())->method('execute')
            ->with($sku, $quantity, $stockStatus);
        $this->indexerProcessor->expects($this->once())->method('reindexList')->with([$productId]);
        $this->cacheCleaner->expects($this->once())
            ->method('clean')
            ->with(
                [$productId],
                $this->callback(
                    function (callable $callback) {
                        $callback();
                        return true;
                    }
                )
            );
        $this->model->execute([$sourceItemMock]);
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [201, 'COC002-Red-M', 10, SourceItemInterface::STATUS_IN_STOCK],
            [202, 'COC002-Black-M', 10, SourceItemInterface::STATUS_OUT_OF_STOCK],
        ];
    }
}
