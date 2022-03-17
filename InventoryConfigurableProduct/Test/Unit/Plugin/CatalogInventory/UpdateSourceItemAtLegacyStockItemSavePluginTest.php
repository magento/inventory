<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\CatalogInventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateSourceItemAtLegacyStockItemSavePlugin;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Unit test for
 * Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateSourceItemAtLegacyStockItemSavePlugin class.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateSourceItemAtLegacyStockItemSavePluginTest extends TestCase
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
     * @var Configurable
     */
    private $configurableTypeMock;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalableMock;

    /**
     * @var UpdateSourceItemAtLegacyStockItemSavePlugin
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
        $this->configurableTypeMock = $this->getMockBuilder(Configurable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->areProductsSalableMock = $this->getMockForAbstractClass(AreProductsSalableInterface::class);
        $this->plugin = new UpdateSourceItemAtLegacyStockItemSavePlugin(
            $this->getProductTypeByIdMock,
            $this->setDataToLegacyStockStatusMock,
            $this->getSkusByProductIdsMock,
            $this->configurableTypeMock,
            $this->areProductsSalableMock
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

        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQty',
                'getIsInStock',
                'getProductId',
                'hasDataChanges',
                'dataHasChangedFor',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects(self::once())->method('getQty')->willReturn($product['qty']);
        $stockItemMock->expects(self::once())->method('hasDataChanges')->willReturn(true);
        $stockItemMock->expects(self::once())
            ->method('dataHasChangedFor')
            ->with('is_in_stock')
            ->willReturn(true);
        $stockItemMock->expects(self::once())->method('getIsInStock')->willReturn(Stock::STOCK_IN_STOCK);
        $stockItemMock->expects($this->exactly(3))->method('getProductId')->willReturn($product['id']);
        $stockItemMock->expects(self::once())
            ->method('getStockStatusChangedAuto')
            ->willReturn(true);
        $this->getProductTypeByIdMock->expects(self::once())
            ->method('execute')
            ->with($product['id'])
            ->willReturn($product['type']);
        $this->getSkusByProductIdsMock->expects(self::once())
            ->method('execute')
            ->willReturn([$product['id'] => $product['sku']]);
        $this->setDataToLegacyStockStatusMock->expects(self::once())
            ->method('execute')
            ->with($product['sku'], (float) $product['qty'], Stock::STOCK_IN_STOCK);
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }

    public function testConfigurableStockAfterLegacySockItemSaveNoChanges()
    {
        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'hasDataChanges',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects(self::once())->method('hasDataChanges')->willReturn(false);
        $stockItemMock->expects(self::never())->method('getStockStatusChangedAuto');
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }
}
