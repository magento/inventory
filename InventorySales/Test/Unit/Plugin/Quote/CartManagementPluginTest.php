<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Plugin\Quote;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfiguration\Model\StockItemConfiguration;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventorySales\Model\ResourceModel\AcquireInventoryLock;
use Magento\InventorySales\Plugin\Quote\CartManagementPlugin;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var GetStockItemConfigurationInterface|MockObject
     */
    private $getStockItemConfiguration;

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
        $this->getStockItemConfiguration = $this->createMock(GetStockItemConfigurationInterface::class);

        $this->plugin = new CartManagementPlugin(
            $this->cartRepository,
            $this->getSkusByProductIds,
            $this->acquireInventoryLock,
            $this->stockByWebsiteIdResolver,
            $this->storeManager,
            $this->reservationExecution,
            $this->getStockItemConfiguration
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
        $this->getStockItemConfiguration->method('execute')
            ->with('simple-1', 3)
            ->willReturn($this->createConfiguredMock(
                StockItemConfigurationInterface::class,
                ['isManageStock' => true]
            ));
        $this->acquireInventoryLock->method('execute')->with('simple-1', 3)->willReturn(true);
        $this->acquireInventoryLock->expects($this->once())->method('release')->with('simple-1', 3);

        $proceed = function (int $receivedCartId) use ($cartId, $expectedOrderId): int {
            $this->assertSame($cartId, $receivedCartId);
            return $expectedOrderId;
        };

        $result = $this->plugin->aroundPlaceOrder($subject, $proceed, $cartId);
        $this->assertSame($expectedOrderId, $result);
    }

    #[DataProvider('manageStockDataProvider')]
    public function testAroundPlaceOrderRespectsEffectiveManageStock(
        bool $useConfigManageStock,
        bool $manageStock,
        bool $globalManageStock,
        bool $expectedManageStock
    ): void {
        $this->setUpActiveQuote(['simple-1']);
        $stockConfiguration = $this->createMock(StockConfigurationInterface::class);
        $stockConfiguration->method('getManageStock')->willReturn((int)$globalManageStock);
        $objectManager = new ObjectManager($this);
        $stockItem = $objectManager->getObject(Item::class, [
            'stockConfiguration' => $stockConfiguration,
            'data' => [
                'use_config_manage_stock' => $useConfigManageStock,
                'manage_stock' => $manageStock
            ]
        ]);
        $stockItem->setStoreId(1);
        $stockItemConfiguration = $objectManager->getObject(
            StockItemConfiguration::class,
            ['stockItem' => $stockItem]
        );
        $this->getStockItemConfiguration->expects($this->once())->method('execute')
            ->with('simple-1', 3)->willReturn($stockItemConfiguration);
        $this->acquireInventoryLock->expects($expectedManageStock ? $this->once() : $this->never())
            ->method('execute')->with('simple-1', 3)->willReturn(true);
        $this->acquireInventoryLock->expects($expectedManageStock ? $this->once() : $this->never())
            ->method('release')->with('simple-1', 3);
        $payment = $this->createMock(PaymentInterface::class);
        $proceed = function (int $cartId, ?PaymentInterface $paymentMethod) use ($payment): int {
            $this->assertSame(36, $cartId);
            $this->assertSame($payment, $paymentMethod);
            return 1001;
        };

        $this->assertSame(1001, $this->plugin->aroundPlaceOrder(
            $this->createMock(CartManagementInterface::class),
            $proceed,
            36,
            $payment
        ));
    }

    public static function manageStockDataProvider(): array
    {
        return [
            'explicitly disabled, global enabled' => [false, false, true, false],
            'explicitly disabled, global disabled' => [false, false, false, false],
            'explicitly enabled, global disabled' => [false, true, false, true],
            'explicitly enabled, global enabled' => [false, true, true, true],
            'inherited disabled, product enabled' => [true, true, false, false],
            'inherited disabled, product disabled' => [true, false, false, false],
            'inherited enabled, product disabled' => [true, false, true, true],
            'inherited enabled, product enabled' => [true, true, true, true]
        ];
    }

    public function testAroundPlaceOrderLocksOnlyManagedProductsInMixedCart(): void
    {
        $this->setUpActiveQuote(['unmanaged', 'managed']);
        $this->setUpStockItemConfigurations(['unmanaged' => false, 'managed' => true]);
        $this->acquireInventoryLock->expects($this->once())->method('execute')
            ->with('managed', 3)->willReturn(true);
        $this->acquireInventoryLock->expects($this->once())->method('release')->with('managed', 3);

        $this->assertSame(1001, $this->plugin->aroundPlaceOrder(
            $this->createMock(CartManagementInterface::class),
            static fn (): int => 1001,
            36
        ));
    }

    public function testAroundPlaceOrderReleasesAcquiredLocksWhenManagedProductLockFails(): void
    {
        $this->setUpActiveQuote(['unmanaged', 'managed-1', 'managed-2']);
        $this->setUpStockItemConfigurations(['unmanaged' => false, 'managed-1' => true, 'managed-2' => true]);
        $this->acquireInventoryLock->expects($this->exactly(2))->method('execute')
            ->willReturnMap([['managed-1', 3, true], ['managed-2', 3, false]]);
        $this->acquireInventoryLock->expects($this->once())->method('release')->with('managed-1', 3);
        $proceed = function (): int {
            $this->fail('Order placement must not proceed after a managed product lock fails.');
        };

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Could not acquire inventory lock for SKU: managed-2. Please try again.');
        $this->plugin->aroundPlaceOrder($this->createMock(CartManagementInterface::class), $proceed, 36);
    }

    public function testAroundPlaceOrderReleasesManagedProductLockWhenOrderPlacementFails(): void
    {
        $this->setUpActiveQuote(['unmanaged', 'managed']);
        $this->setUpStockItemConfigurations(['unmanaged' => false, 'managed' => true]);
        $this->acquireInventoryLock->expects($this->once())->method('execute')
            ->with('managed', 3)->willReturn(true);
        $this->acquireInventoryLock->expects($this->once())->method('release')->with('managed', 3);
        $exception = new LocalizedException(__('Order placement failed.'));
        $proceed = static function () use ($exception): int {
            throw $exception;
        };

        $this->expectExceptionObject($exception);
        $this->plugin->aroundPlaceOrder($this->createMock(CartManagementInterface::class), $proceed, 36);
    }

    public function testAroundPlaceOrderBypassesLocksWhenReservationsAreNotDeferred(): void
    {
        $this->reservationExecution->method('isDeferred')->willReturn(false);
        $this->cartRepository->expects($this->never())->method('getActive');
        $this->getStockItemConfiguration->expects($this->never())->method('execute');
        $this->acquireInventoryLock->expects($this->never())->method('execute');
        $this->acquireInventoryLock->expects($this->never())->method('release');

        $this->assertSame(1001, $this->plugin->aroundPlaceOrder(
            $this->createMock(CartManagementInterface::class),
            static fn (): int => 1001,
            36
        ));
    }

    /**
     * @param string[] $skus
     */
    private function setUpActiveQuote(array $skus): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(1);
        $items = [];
        foreach (array_keys($skus) as $productId) {
            $items[] = new DataObject(['product_id' => $productId]);
        }
        $quote->method('getAllVisibleItems')->willReturn($items);
        $this->cartRepository->method('getActive')->with(36)->willReturn($quote);
        $this->reservationExecution->method('isDeferred')->willReturn(true);
        $this->storeManager->method('getStore')->with(1)->willReturn(
            $this->createConfiguredMock(StoreInterface::class, ['getWebsiteId' => 2])
        );
        $this->stockByWebsiteIdResolver->method('execute')->with(2)->willReturn(
            $this->createConfiguredMock(StockInterface::class, ['getStockId' => 3])
        );
        $this->getSkusByProductIds->method('execute')->with(array_keys($skus))->willReturn($skus);
    }

    /**
     * @param array<string, bool> $manageStockBySku
     */
    private function setUpStockItemConfigurations(array $manageStockBySku): void
    {
        $configurations = [];
        foreach ($manageStockBySku as $sku => $manageStock) {
            $configurations[] = [
                $sku,
                3,
                $this->createConfiguredMock(StockItemConfigurationInterface::class, ['isManageStock' => $manageStock])
            ];
        }
        $this->getStockItemConfiguration->method('execute')->willReturnMap($configurations);
    }
}
