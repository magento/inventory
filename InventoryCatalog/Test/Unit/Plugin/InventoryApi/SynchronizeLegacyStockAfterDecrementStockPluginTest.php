<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\InventoryApi;

use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Model\ResourceModel\DecrementQtyForLegacyStock;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalog\Plugin\InventoryApi\SynchronizeLegacyStockAfterDecrementStockPlugin;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SynchronizeLegacyStockAfterDecrementStockPluginTest extends TestCase
{
    /**
     * @var DecrementSourceItemQty|MockObject
     */
    private $subjectMock;

    /**
     * @var SourceItemInterface|MockObject
     */
    private $sourceItemMock;

    /**
     * @var SynchronizeLegacyStockAfterDecrementStockPlugin
     */
    private $plugin;

    /**
     * @var DecrementQtyForLegacyStock|MockObject
     */
    private $decrementQuantityForLegacyCatalogInventoryMock;

    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private $getProductIdsBySkusMock;

    /**
     * @var Processor|MockObject
     */
    private $indexerProcessorMock;

    /**
     * @var SetDataToLegacyStockStatus|MockObject
     */
    private $setDataToLegacyStockStatusMock;

    /**
     * @var StockItemCriteriaInterfaceFactory|MockObject
     */
    private $legacyStockItemCriteriaFactoryMock;

    /**
     * @var StockItemRepositoryInterface|MockObject
     */
    private $legacyStockItemRepositoryMock;

    /**
     * @var StockStateProviderInterface|MockObject
     */
    private $stockStateProviderMock;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private $defaultSourceProviderMock;

    public function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(DecrementSourceItemQty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceItemMock = $this->getMockBuilder(SourceItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->decrementQuantityForLegacyCatalogInventoryMock = $this->getMockBuilder(DecrementQtyForLegacyStock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getProductIdsBySkusMock = $this->getMockBuilder(GetProductIdsBySkusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerProcessorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setDataToLegacyStockStatusMock = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyStockItemCriteriaFactoryMock = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyStockItemRepositoryMock = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockStateProviderMock = $this->getMockBuilder(StockStateProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultSourceProviderMock = $this->getMockBuilder(DefaultSourceProviderInterface::class)
            ->getMockForAbstractClass();

        $this->plugin = new SynchronizeLegacyStockAfterDecrementStockPlugin(
            $this->decrementQuantityForLegacyCatalogInventoryMock,
            $this->getProductIdsBySkusMock,
            $this->indexerProcessorMock,
            $this->setDataToLegacyStockStatusMock,
            $this->legacyStockItemCriteriaFactoryMock,
            $this->legacyStockItemRepositoryMock,
            $this->stockStateProviderMock,
            $this->defaultSourceProviderMock
        );
    }

    /**
     * Test to verify the change in salable quantity for `default` stock
     *
     * @param $sourceCode
     * @param $productId
     * @param $itemSku
     * @param $qty
     * @param $stockStatus
     * @dataProvider getDataProvider
     * @return void
     */
    public function testAfterExecute($sourceCode, $productId, $itemSku, $qty, $stockStatus): void
    {
        $defaultSourceCode = 'default';
        $this->defaultSourceProviderMock->expects($this->atLeastOnce())->method('getCode')
            ->willReturn($defaultSourceCode);
        $this->sourceItemMock->method('getSku')->willReturn($itemSku);
        $this->sourceItemMock->method('getSourceCode')->willReturn($sourceCode);
        $this->sourceItemMock->method('getQuantity')->willReturn((float)$qty);
        $this->sourceItemMock->method('getStatus')->willReturn($stockStatus);

        if ($sourceCode !== $defaultSourceCode) {
            $this->sourceItemMock->expects($this->any())->method('getSku')->willReturn($itemSku);
            $this->getProductIdsBySkusMock->expects($this->never())->method('execute');
        } else {
            $this->sourceItemMock->expects($this->exactly(2))->method('getSku')->willReturn($itemSku);
            $this->getProductIdsBySkusMock->expects($this->once())->method('execute')
                ->willReturn([$itemSku => $productId]);

            $this->getProductIdsBySkusMock->expects($this->atLeastOnce())->method('execute')->with([$itemSku])
                ->willReturn([$itemSku => $productId]);

            $stockItemMock = $this->getMockBuilder(StockItemInterface::class)
                ->onlyMethods(['getManageStock', 'setIsInStock', 'setQty'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $stockItemMock->expects($this->once())->method('getManageStock')->willReturn(true);
            $stockItemMock->expects($this->once())->method('setIsInStock')->willReturn($stockStatus);
            $stockItemMock->expects($this->once())->method('setQty')->willReturn($qty);

            $this->sourceItemMock->expects($this->exactly(2))->method('getQuantity')->willReturn($qty);
            $stockItems = [
                $stockItemMock,
            ];

            $stockItemCollection = $this->createConfiguredMock(
                StockItemCollectionInterface::class,
                ['getItems' => $stockItems]
            );
            $searchCriteria = $this->createMock(StockItemCriteriaInterface::class);
            $searchCriteria->expects($this->exactly(2))
                ->method('addFilter')
                ->withConsecutive(
                    [StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productId],
                    [StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID]
                )
                ->willReturnSelf();
            $stockItemCollection->expects($this->once())->method('getTotalCount')->willReturn(1);
            $this->legacyStockItemRepositoryMock->method('getList')
                ->willReturn($stockItemCollection);
            $this->legacyStockItemCriteriaFactoryMock->method('create')
                ->willReturn($searchCriteria);

            $this->stockStateProviderMock->expects($this->once())->method('verifyStock')
                ->with($stockItemMock)
                ->willReturn(true);

            $this->setDataToLegacyStockStatusMock->expects($this->once())->method('execute')
                ->with($itemSku, $qty, $stockStatus)
                ->willReturnSelf();

            $this->indexerProcessorMock->expects($this->once())
                ->method('reindexList')
                ->with([$productId])
                ->willReturnSelf();
        }

        $sourceItemDecrementData[] = [
            'source_item' => $this->sourceItemMock,
            'qty_to_decrement' => $qty
        ];
        $this->plugin->afterExecute($this->subjectMock, null, $sourceItemDecrementData);
    }

    /**
     * @return array[]
     */
    public function getDataProvider(): array
    {
        return [
            ['default', 1, 'SKU-1', 1.0, 1],
            ['custom_source', 1, 'SKU-1', 1.0, 0]
        ];
    }
}
