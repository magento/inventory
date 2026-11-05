<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesFrontendUi\Test\Unit\Plugin\Block\Stockqty;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogFrontendUi\Model\IsSalableQtyAvailableForDisplaying;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySalesFrontendUi\Plugin\Block\Stockqty\AbstractStockqtyPlugin;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractStockqtyPluginTest extends TestCase
{
    private const SKU = 'simple';
    private const STOCK_ID = 2;

    /**
     * @var GetStockItemConfigurationInterface|MockObject
     */
    private $getStockItemConfiguration;

    /**
     * @var GetProductSalableQtyInterface|MockObject
     */
    private $getProductSalableQty;

    /**
     * @var IsSalableQtyAvailableForDisplaying|MockObject
     */
    private $qtyLeftChecker;

    /**
     * @var AbstractStockqty|MockObject
     */
    private $subject;

    /**
     * @var AbstractStockqtyPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $store = $this->createStub(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $product = $this->createStub(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn(self::SKU);
        $product->method('getStore')->willReturn($store);
        $this->subject = $this->createStub(AbstractStockqty::class);
        $this->subject->method('getProduct')->willReturn($product);

        $stock = $this->createStub(StockInterface::class);
        $stock->method('getStockId')->willReturn(self::STOCK_ID);
        $stockByWebsiteId = $this->createStub(StockByWebsiteIdResolverInterface::class);
        $stockByWebsiteId->method('execute')->willReturn($stock);
        $isSourceItemManagementAllowed = $this->createStub(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $isSourceItemManagementAllowed->method('execute')->willReturn(true);

        $this->getStockItemConfiguration = $this->createMock(GetStockItemConfigurationInterface::class);
        $this->getProductSalableQty = $this->createMock(GetProductSalableQtyInterface::class);
        $this->qtyLeftChecker = $this->createMock(IsSalableQtyAvailableForDisplaying::class);

        $this->plugin = new AbstractStockqtyPlugin(
            $stockByWebsiteId,
            $this->getStockItemConfiguration,
            $this->getProductSalableQty,
            $isSourceItemManagementAllowed,
            $this->qtyLeftChecker
        );
    }

    public function testMessageIsHiddenWhenSkuIsNotAssignedToWebsiteStock(): void
    {
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with(self::SKU, self::STOCK_ID)
            ->willThrowException(new SkuIsNotAssignedToStockException(__('Not assigned.')));
        $this->getProductSalableQty->expects($this->never())
            ->method('execute');
        $this->qtyLeftChecker->expects($this->never())
            ->method('execute');

        $this->assertFalse($this->plugin->aroundIsMsgVisible($this->subject, fn () => true));
    }

    public function testMessageVisibilityFollowsSalableQtyWhenSkuIsAssigned(): void
    {
        $stockItemConfig = $this->createStub(StockItemConfigurationInterface::class);
        $stockItemConfig->method('isManageStock')->willReturn(true);
        $this->getStockItemConfiguration->expects($this->once())
            ->method('execute')
            ->with(self::SKU, self::STOCK_ID)
            ->willReturn($stockItemConfig);
        $this->getProductSalableQty->expects($this->once())
            ->method('execute')
            ->with(self::SKU, self::STOCK_ID)
            ->willReturn(3.0);
        $this->qtyLeftChecker->expects($this->once())
            ->method('execute')
            ->with(3.0)
            ->willReturn(true);

        $this->assertTrue($this->plugin->aroundIsMsgVisible($this->subject, fn () => false));
    }
}
