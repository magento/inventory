<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventorySales\Model\ResourceModel\AcquireInventoryLock;
use Magento\InventorySales\Plugin\Quote\CartManagementPlugin;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartManagementPluginTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var GetSkusByProductIdsInterface|MockObject
     */
    private $getSkusByProductIds;

    /**
     * @var AcquireInventoryLock|MockObject
     */
    private $acquireInventoryLock;

    /**
     * @var StockByWebsiteIdResolverInterface|MockObject
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ReservationExecutionInterface|MockObject
     */
    private $reservationExecution;

    /**
     * @var CartManagementPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->getSkusByProductIds = $this->createMock(GetSkusByProductIdsInterface::class);
        $this->acquireInventoryLock = $this->createMock(AcquireInventoryLock::class);
        $this->stockByWebsiteIdResolver = $this->createMock(StockByWebsiteIdResolverInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->reservationExecution = $this->createMock(ReservationExecutionInterface::class);

        $this->plugin = new CartManagementPlugin(
            $this->cartRepository,
            $this->getSkusByProductIds,
            $this->acquireInventoryLock,
            $this->stockByWebsiteIdResolver,
            $this->storeManager,
            $this->reservationExecution
        );
    }

    public function testAroundPlaceOrderFallsBackToGetForInactiveQuote(): void
    {
        $cartId = 36;
        $expectedOrderId = 1001;

        $subject = $this->createMock(CartManagementInterface::class);
        
        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(1);
        $item = new class {
            public function getProductId(): int
            {
                return 42;
            }
        };
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $store = $this->createConfiguredMock(StoreInterface::class, ['getWebsiteId' => 2]);
        $stock = $this->createConfiguredMock(StockInterface::class, ['getStockId' => 3]);

        $this->reservationExecution->method('isDeferred')->willReturn(true);
        $this->cartRepository->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->willThrowException(NoSuchEntityException::singleField('cartId', $cartId));
        $this->cartRepository->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($quote);
        $this->storeManager->method('getStore')->with(1)->willReturn($store);
        $this->stockByWebsiteIdResolver->method('execute')->with(2)->willReturn($stock);
        $this->getSkusByProductIds->method('execute')->with([42])->willReturn(['simple-1']);
        $this->acquireInventoryLock->method('execute')->with('simple-1', 3)->willReturn(true);
        $this->acquireInventoryLock->expects($this->once())->method('release')->with('simple-1', 3);

        $proceed = function (int $receivedCartId) use ($cartId, $expectedOrderId): int {
            $this->assertSame($cartId, $receivedCartId);
            return $expectedOrderId;
        };

        $result = $this->plugin->aroundPlaceOrder($subject, $proceed, $cartId);
        $this->assertSame($expectedOrderId, $result);
    }
}
