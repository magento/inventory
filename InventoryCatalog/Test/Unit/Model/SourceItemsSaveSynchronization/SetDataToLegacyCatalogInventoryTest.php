<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model\SourceItemsSaveSynchronization;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization\SetDataToLegacyCatalogInventory;
use Magento\InventoryCatalog\Model\UpdateDefaultStock;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;
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
     * @var GetLegacyStockItemsInterface&MockObject
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
     * @var UpdateDefaultStock&MockObject
     */
    private $updateDefaultStock;

    /**
     * @var ProductSalabilityChangeProcessorInterface&MockObject
     */
    private $productSalabilityChangeProcessor;

    protected function setUp(): void
    {
        $this->setDataToLegacyStockItem = $this->getMockBuilder(SetDataToLegacyStockItem::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMock();
        $this->getLegacyStockItems = $this->getMockBuilder(GetLegacyStockItemsInterface::class)
            ->onlyMethods(['execute'])->disableOriginalConstructor()->getMock();
        $this->getProductIdsBySkus = $this->getMockBuilder(GetProductIdsBySkusInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->stockStateProvider = $this->getMockBuilder(StockStateProviderInterface::class)
            ->onlyMethods(['verifyStock'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->updateDefaultStock = $this->createMock(UpdateDefaultStock::class);
        $this->productSalabilityChangeProcessor = $this->createMock(ProductSalabilityChangeProcessorInterface::class);
        $this->model = new SetDataToLegacyCatalogInventory(
            $this->setDataToLegacyStockItem,
            $this->getProductIdsBySkus,
            $this->stockStateProvider,
            $this->getLegacyStockItems,
            $this->updateDefaultStock,
            $this->productSalabilityChangeProcessor
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
        $this->getProductIdsBySkus->expects($this->atLeastOnce())->method('execute')->with($skus)
            ->willReturn([$sku => $productId]);
        $stockItemMock = $this->createMock(StockItemInterface::class);
        $stockItemMock->expects($this->once())->method('getManageStock')->willReturn(true);
        $stockItemMock->expects($this->once())->method('setIsInStock')->willReturn($stockStatus);
        $stockItemMock->expects($this->once())->method('setQty')->willReturn($quantity);
        $stockItemMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $stockItemMock->expects($this->once())->method('getIsInStock')->willReturn($stockStatus);
        $stockItemMock->expects($this->once())->method('getQty')->willReturn($quantity);
        $this->getLegacyStockItems->expects($this->once())->method('execute')->willReturn([$stockItemMock]);
        $this->stockStateProvider->expects($this->once())->method('verifyStock')->with($stockItemMock)
            ->willReturn(true);

        $this->updateDefaultStock->expects($this->once())
            ->method('execute')
            ->with($skus)
            ->willReturn($skus);
        $this->productSalabilityChangeProcessor->expects($this->once())
            ->method('execute')
            ->with($skus);
        $this->setDataToLegacyStockItem->expects($this->once())->method('execute')
            ->with($sku, $quantity, $stockStatus);
        $this->model->execute([$sourceItemMock]);
    }

    /**
     * @return array
     */
    public static function getDataProvider(): array
    {
        return [
            [201, 'COC002-Red-M', 10, SourceItemInterface::STATUS_IN_STOCK],
            [202, 'COC002-Black-M', 10, SourceItemInterface::STATUS_OUT_OF_STOCK],
        ];
    }
}
