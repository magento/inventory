<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model\ReturnProcessor;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItemModel;
use Magento\Sales\Model\Order\Item as OrderItemModel;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySales\Model\ReturnProcessor\GetInvoicedItemsPerSourceByPriority;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItemFactory;
use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItem;
use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItemsResultFactory;
use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItemsResult;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test returning invoiced items per source by priority
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetInvoicedItemsPerSourceByPriorityTest extends TestCase
{
    /**
     * @var GetInvoicedItemsPerSourceByPriority|MockObject
     */
    private $model;

    /**
     * @var GetSkuFromOrderItemInterface|MockObject
     */
    private $getSkuFromOrderItem;

    /**
     * @var StockByWebsiteIdResolverInterface|MockObject
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface|MockObject
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetSourceItemsBySkuInterface|MockObject
     */
    private $getSourceItemsBySku;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private $defaultSourceProvider;

    /**
     * @var SourceDeductedOrderItemFactory|MockObject
     */
    private $sourceDeductedOrderItemFactory;

    /**
     * @var SourceDeductedOrderItemsResultFactory|MockObject
     */
    private $sourceDeductedOrderItemsResultFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->getSkuFromOrderItem = $this->getMockForAbstractClass(GetSkuFromOrderItemInterface::class);
        $this->stockByWebsiteIdResolver = $this->getMockForAbstractClass(StockByWebsiteIdResolverInterface::class);
        $this->getSourcesAssignedToStockOrderedByPriority = $this->getMockForAbstractClass(
            GetSourcesAssignedToStockOrderedByPriorityInterface::class
        );
        $this->getSourceItemsBySku = $this->getMockForAbstractClass(GetSourceItemsBySkuInterface::class);
        $this->defaultSourceProvider = $this->getMockForAbstractClass(DefaultSourceProviderInterface::class);
        $this->sourceDeductedOrderItemFactory = $this->createMock(SourceDeductedOrderItemFactory::class);
        $this->sourceDeductedOrderItemsResultFactory = $this->createMock(SourceDeductedOrderItemsResultFactory::class);
        $this->model = $objectManager->getObject(
            GetInvoicedItemsPerSourceByPriority::class,
            [
                'getSkuFromOrderItem' => $this->getSkuFromOrderItem,
                'stockByWebsiteIdResolver' => $this->stockByWebsiteIdResolver,
                'getSourcesAssignedToStockOrderedByPriority' => $this->getSourcesAssignedToStockOrderedByPriority,
                'getSourceItemsBySku' => $this->getSourceItemsBySku,
                'defaultSourceProvider' => $this->defaultSourceProvider,
                'sourceDeductedOrderItemFactory' => $this->sourceDeductedOrderItemFactory,
                'sourceDeductedOrderItemsResultFactory' => $this->sourceDeductedOrderItemsResultFactory,
            ]
        );
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $returnToStockItems
     * @param string $itemSku
     * @param int $invoiceItemQty
     * @param int $websiteId
     * @param int $stockId
     * @param string $defaultSourceCode
     */
    public function testExecute(
        array $returnToStockItems,
        string $itemSku,
        int $invoiceItemQty,
        int $websiteId,
        int $stockId,
        string $defaultSourceCode
    ): void {
        $orderItem = $this->createMock(OrderItemModel::class);
        $orderItem->method('getId')->willReturn(1);
        $orderItem->method('getIsVirtual')->willReturn(true);
        $orderItem->method('isDummy')->willReturn(false);
        $invoiceItem = $this->createMock(InvoiceItemModel::class);
        $invoiceItem->method('getOrderItem')
            ->willReturn($orderItem);
        $invoiceItem->method('getQty')
            ->willReturn($invoiceItemQty);
        $invoice = $this->createMock(InvoiceModel::class);
        $invoice->method('getItems')
            ->willReturn([$invoiceItem]);
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')
            ->willReturn($websiteId);
        /** @var \Magento\Sales\Model\Order|MockObject $order */
        $order = $this->createMock(Order::class);
        $order->method('getInvoiceCollection')
            ->willReturn([$invoice]);
        $order->method('getStore')
            ->willReturn($store);
        $this->getSkuFromOrderItem->method('execute')->with($orderItem)
            ->willReturn($itemSku);
        $stock = $this->getMockForAbstractClass(StockInterface::class);
        $stock->method('getStockId')
            ->willReturn($stockId);
        $this->stockByWebsiteIdResolver->method('execute')
            ->willReturn($stock);
        $this->defaultSourceProvider->method('getCode')
            ->willReturn($defaultSourceCode);
        $this->getSourceItemsBySku->method('execute')->with($itemSku)
            ->willReturn([]);
        $this->getSourcesAssignedToStockOrderedByPriority->method('execute')->with($stockId)
            ->willReturn([]);
        $sourceDeductedOrderItem = $this->createMock(SourceDeductedOrderItem::class);
        $this->sourceDeductedOrderItemFactory->method('create')->with([
            'sku' => $itemSku,
            'quantity' => $invoiceItemQty
        ])->willReturn($sourceDeductedOrderItem);
        $sourceDeductedOrderItemsResult = $this->createMock(SourceDeductedOrderItemsResult::class);
        $this->sourceDeductedOrderItemsResultFactory->method('create')->with([
            'sourceCode' => $defaultSourceCode,
            'items' => [$sourceDeductedOrderItem]
        ])->willReturn($sourceDeductedOrderItemsResult);
        $actualInvoiceItemsResult = $this->model->execute($order, $returnToStockItems);
        $this->assertEquals([$sourceDeductedOrderItemsResult], $actualInvoiceItemsResult);
    }

    /**
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                'returnToStockItems' => [1],
                'itemSku' => '321458566',
                'invoiceItemQty' => 1,
                'websiteId' => 1,
                'stockId' => 1,
                'defaultSourceCode' => 'default'
            ],
            [
                'returnToStockItems' => [1],
                'itemSku' => 'SKU-1',
                'invoiceItemQty' => 1,
                'websiteId' => 1,
                'stockId' => 1,
                'defaultSourceCode' => 'default'
            ],
        ];
    }
}
