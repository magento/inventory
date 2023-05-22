<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\CatalogInventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition\IsConfigurableProductChildrenSalable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateLegacyStockStatusForConfigurableProduct;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

/**
 * Unit test for
 * Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateLegacyStockStatusForConfigurableProduct class.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateLegacyStockStatusForConfigurableProductTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $getProductTypeByIdMock;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatusMock;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $getSkusByProductIdsMock;

    /**
     * @var IsConfigurableProductChildrenSalable
     */
    private $isConfigurableProductChildrenSalable;

    /**
     * @var GetLegacyStockItem
     */
    private $getLegacyStockItemMock;

    /**
     * @var UpdateLegacyStockStatusForConfigurableProduct
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->getProductTypeByIdMock = $this->getMockBuilder(GetProductTypeById::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setDataToLegacyStockStatusMock = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getSkusByProductIdsMock = $this->getMockForAbstractClass(GetSkusByProductIdsInterface::class);
        $this->isConfigurableProductChildrenSalable = $this->createMock(IsConfigurableProductChildrenSalable::class);
        $this->getLegacyStockItemMock = $this->getMockBuilder(GetLegacyStockItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new UpdateLegacyStockStatusForConfigurableProduct(
            $this->getProductTypeByIdMock,
            $this->setDataToLegacyStockStatusMock,
            $this->getSkusByProductIdsMock,
            $this->getLegacyStockItemMock,
            $this->isConfigurableProductChildrenSalable
        );
    }

    public function testConfigurableStockAfterLegacySockItemSave()
    {
        $product = [
            'id' => 1,
            'type' => Configurable::TYPE_CODE,
            'sku' => 'conf_1',
            'qty' => 0
        ];
        $childIds = [2,3];
        $childSkus = ['sku2', 'sku3'];

        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQty',
                'getIsInStock',
                'getProductId',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects($this->once())->method('getQty')->willReturn($product['qty']);
        $stockItemMock->expects($this->once())->method('getIsInStock')->willReturn(Stock::STOCK_IN_STOCK);
        $stockItemMock->method('getProductId')->willReturn($product['id']);
        $stockItemMock->expects($this->once())
            ->method('getStockStatusChangedAuto')
            ->willReturn(false);
        $stockItemDBMock = $this->getMockForAbstractClass(StockItemInterface::class);
        $stockItemDBMock->expects($this->once())->method('getIsInStock')->willReturn(Stock::STOCK_OUT_OF_STOCK);
        $this->getLegacyStockItemMock->expects($this->once()) //detected stock change event
            ->method('execute')
            ->with($product['sku'])
            ->willReturn($stockItemDBMock);
        $this->getProductTypeByIdMock->expects($this->once())
            ->method('execute')
            ->with($product['id'])
            ->willReturn($product['type']);
        $this->getSkusByProductIdsMock->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturnMap([
                [[$product['id']], [$product['id'] => $product['sku']]],
                [$childIds, $childSkus]
            ]);
        $this->isConfigurableProductChildrenSalable->expects($this->once())
            ->method('execute')
            ->with($product['sku'], Stock::DEFAULT_STOCK_ID)
            ->willReturn(true);
        $this->setDataToLegacyStockStatusMock->expects($this->once())
            ->method('execute')
            ->with($product['sku'], (float) $product['qty'], Stock::STOCK_IN_STOCK);
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }

    public function testConfigurableStockAfterLegacySockItemSaveNegativeScenario()
    {
        $product = [
            'id' => 1,
            'type' => Configurable::TYPE_CODE,
            'sku' => 'conf_1',
            'qty' => 0
        ];
        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQty',
                'getIsInStock',
                'getProductId',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects($this->once())->method('getIsInStock')->willReturn(true);
        $stockItemMock->expects($this->atLeastOnce())->method('getProductId')->willReturn($product['id']);
        $this->getProductTypeByIdMock->expects($this->once())
            ->method('execute')
            ->with($product['id'])
            ->willReturn($product['type']);
        $stockItemMock->expects($this->once())
            ->method('getStockStatusChangedAuto')
            ->willReturn(false);
        $stockItemDBMock = $this->getMockForAbstractClass(StockItemInterface::class);
        $stockItemDBMock->expects($this->once())->method('getIsInStock')->willReturn(Stock::STOCK_IN_STOCK);
        $this->getLegacyStockItemMock->expects($this->once()) // stock status wa not changed
            ->method('execute')
            ->with($product['sku'])
            ->willReturn($stockItemDBMock);
        $this->getSkusByProductIdsMock->expects($this->once())
            ->method('execute')
            ->willReturn([$product['id'] => $product['sku']]);
        $stockItemMock->expects($this->never())->method('getQty');
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }
}
