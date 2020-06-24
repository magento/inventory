<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class NegativeMinQtyPlaceOrderTest extends TestCase
{
    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var SaveStockItemConfigurationInterface
     */
    private $saveStockItemConfiguration;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->isProductSalableForRequestedQtyRequestFactory = Bootstrap::getObjectManager()
            ->get(IsProductSalableForRequestedQtyRequestInterfaceFactory::class);
        $this->areProductsSalableForRequestedQty = Bootstrap::getObjectManager()->get(
            AreProductsSalableForRequestedQtyInterface::class
        );
        $this->getProductSalableQty = Bootstrap::getObjectManager()->get(GetProductSalableQtyInterface::class);
        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(
            GetStockItemConfigurationInterface::class
        );
        $this->saveStockItemConfiguration = Bootstrap::getObjectManager()->get(
            SaveStockItemConfigurationInterface::class
        );

        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(
            SearchCriteriaBuilder::class
        );

        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
        $this->stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);
        $this->storeRepository = Bootstrap::getObjectManager()->get(StoreRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->cartItemFactory = Bootstrap::getObjectManager()->get(CartItemInterfaceFactory::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testIsProductOutOfStockAfterPlacingAnOrder(): void
    {
        $sku = 'SKU-1';
        $stockId = 10;
        //Initial amount of SKU-1 on StockId = 10 is 8.5
        //Setting MinQty threshold as negative value we increase the Salable Quantity
        //Salable Quantity: 8.5 - (-4.5) = 13
        $quoteItemQty = 13;

        // Before backorder is set, Salable Quantity is 8.5
        $this->assertEquals(8.5, $this->getProductSalableQty->execute($sku, $stockId));
        $this->assertFalse($this->isSalable($sku, 13, $stockId));

        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setUseConfigMinQty(false);
        $stockItemConfiguration->setMinQty(-4.5);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        // Before backorder is set, Salable Quantity is 8.5
        $this->assertEquals(8.5, $this->getProductSalableQty->execute($sku, $stockId));
        $this->assertFalse($this->isSalable($sku, 13, $stockId));

        $stockItemConfiguration->setUseConfigBackorders(false);
        $stockItemConfiguration->setBackorders(StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        // After backorder is set, Salable Quantity is 8.5 - (-4.5) = 13
        $this->assertEquals(13, $this->getProductSalableQty->execute($sku, $stockId));
        $this->assertTrue($this->isSalable($sku, 13, $stockId));
        $this->assertFalse($this->isSalable($sku, 14, $stockId));

        $cart = $this->getCartByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        $orderId = $this->cartManagement->placeOrder($cart->getId());
        $this->assertFalse($this->isSalable($sku, 1, $stockId));
        $this->deleteOrderById((int)$orderId);

        // Now the Salable Quantity of Product SKU-1 is infinite
        $stockItemConfiguration->setMinQty(0);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);
        $this->assertTrue($this->isSalable($sku, 1, $stockId));

        // Positive MinQty works the same as MinQty = 0 , so that Salable Quantity is infinite
        $stockItemConfiguration->setMinQty(10);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);
        $this->assertTrue($this->isSalable($sku, 100, $stockId));
    }

    /**
     * Create cart for given stock.
     *
     * @param int $stockId
     * @return CartInterface
     */
    private function getCartByStockId(int $stockId): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        /** @var StockInterface $stock */
        $stock = $this->stockRepository->get($stockId);
        /** @var SalesChannelInterface[] $salesChannels */
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        $storeCode = 'store_for_';
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() == SalesChannelInterface::TYPE_WEBSITE) {
                $storeCode .= $salesChannel->getCode();
                break;
            }
        }
        /** @var StoreInterface $store */
        $store = $this->storeRepository->get($storeCode);
        $this->storeManager->setCurrentStore($storeCode);
        $cart->setStoreId($store->getId());

        return $cart;
    }

    /**
     * Delete order.
     *
     * @param int $orderId
     */
    private function deleteOrderById(int $orderId)
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Build cart item for given product, cart and quantity.
     *
     * @param ProductInterface $product
     * @param float $quoteItemQty
     * @param int $cartId
     * @return CartItemInterface
     */
    private function getCartItem(ProductInterface $product, float $quoteItemQty, int $cartId): CartItemInterface
    {
        /** @var CartItemInterface $cartItem */
        $cartItem =
            $this->cartItemFactory->create(
                [
                    'data' => [
                        CartItemInterface::KEY_SKU => $product->getSku(),
                        CartItemInterface::KEY_QTY => $quoteItemQty,
                        CartItemInterface::KEY_QUOTE_ID => $cartId,
                        'product_id' => $product->getId(),
                        'product' => $product,
                    ],
                ]
            );
        return $cartItem;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();
    }

    /**
     * @param string $sku
     * @param float $qty
     * @param int $stockId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isSalable(string $sku, float $qty, int $stockId): bool
    {
        $request = $this->isProductSalableForRequestedQtyRequestFactory->create(
            [
                'sku' => $sku,
                'qty' => $qty,
            ]
        );
        $result = $this->areProductsSalableForRequestedQty->execute([$request], $stockId);
        $result = current($result);

        return $result->isSalable();
    }
}
