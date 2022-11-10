<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Order;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCartFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\ConfigurableProduct\Test\Fixture\AddProductToCart as AddConfigurableProductToCartFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\GroupedProduct\Test\Fixture\AddProductToCart as AddGroupedProductToCartFixture;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryApi\Test\Fixture\DeleteSourceItems as DeleteSourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PlaceOrderOnNotDefaultStockTest extends TestCase
{
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
     * @var CartRepositoryInterface
     */
    private $cartRepository;

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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var SaveStockItemConfigurationInterface
     */
    private $saveStockItemConfiguration;

    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
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
        $this->getStockItemConfiguration =
            Bootstrap::getObjectManager()->get(GetStockItemConfigurationInterface::class);
        $this->saveStockItemConfiguration =
            Bootstrap::getObjectManager()->get(SaveStockItemConfigurationInterface::class);
        $this->cacheStorage = Bootstrap::getObjectManager()->get(CacheStorage::class);
        $this->areProductsSalable = Bootstrap::getObjectManager()->get(AreProductsSalableInterface::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
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
     */
    public function testPlaceOrderWithInStockProduct()
    {
        $sku = 'SKU-2';
        $stockId = 30;
        $quoteItemQty = 2.2;

        $this->setStockItemConfigIsDecimal($sku, $stockId);
        $cart = $this->getCartByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);
        $orderId = $this->cartManagement->placeOrder($cart->getId());

        self::assertNotNull($orderId);
        self::assertNull($this->orderRepository->get($orderId)->getItems()[0]->getQtyBackordered());

        $this->deleteOrderById((int)$orderId);
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
     */
    public function testPlaceOrderWithOutOffStockProduct()
    {
        $sku = 'SKU-2';
        $stockId = 30;
        $quoteItemQty = 6.2;

        $this->setStockItemConfigIsDecimal($sku, $stockId);
        $cart = $this->getCartByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        self::expectException(LocalizedException::class);
        $orderId = $this->cartManagement->placeOrder($cart->getId());

        self::assertNull($orderId);
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
     * @magentoConfigFixture store_for_global_website_store cataloginventory/item_options/backorders 1
     *
     * @magentoDbIsolation disabled
     */
    public function testPlaceOrderWithOutOffStockProductAndBackOrdersTurnedOn()
    {
        $sku = 'SKU-2';
        $stockId = 30;
        $quoteItemQty = 6.5;
        $this->setStockItemConfigIsDecimal($sku, $stockId);
        $cart = $this->getCartByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $this->cacheStorage->delete($sku);
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);
        $orderId = $this->cartManagement->placeOrder($cart->getId());
        self::assertNotNull($orderId);
        /**
         * This assert can be introduced once https://github.com/magento/magento2/pull/29881
         * has been merged
         */
        //self::assertEquals($this->orderRepository->get($orderId)->getItems()[0]->getQtyBackordered(), 1.5);

        //cleanup
        $this->deleteOrderById((int)$orderId);
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
     * @magentoConfigFixture current_store cataloginventory/item_options/manage_stock 0
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testPlaceOrderWithOutOffStockProductAndManageStockTurnedOff()
    {
        $sku = 'SKU-2';
        $stockId = 30;
        $quoteItemQty = 6.5;

        $this->setStockItemConfigIsDecimal($sku, $stockId);
        $this->setStockItemManageStockFalse($sku, $stockId);
        $cart = $this->getCartByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        $orderId = $this->cartManagement->placeOrder($cart->getId());

        self::assertNotNull($orderId);

        //cleanup
        $this->deleteOrderById((int)$orderId);
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'src1'),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src1.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(AttributeFixture::class, ['options' => [['label' => 'option1', 'sort_order' => 0]]], as: 'attr'),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ConfigurableProductFixture::class, ['_options' => ['$attr$'], '_links' => ['$p1$']], 'cp1'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => 'default', 'quantity' => 0],
                ['sku' => '$p1.sku$', 'source_code' => '$src1.source_code$', 'quantity' => 1],
            ]
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddConfigurableProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$cp1.id$', 'child_product_id' => '$p1.id$', 'qty' => 1],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(ShipmentFixture::class, ['order_id' => '$order.id$', 'items' => [['product_id' => '$cp1.id$']]]),
    ]
    public function testConfigurableProductShouldBeBackInStockWhenChildProductIsBackInStock(): void
    {
        $simpleProductSKU = $this->fixtures->get('p1')->getSku();
        $configurableProductSKU = $this->fixtures->get('cp1')->getSku();
        $stock = $this->fixtures->get('stk2');
        $source = $this->fixtures->get('src1');
        $this->assertFalse(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertFalse(
            current($this->areProductsSalable->execute([$configurableProductSKU], $stock->getStockId()))->isSalable()
        );
        $sourceItem = $this->sourceItemFactory->create(
            [
                'data' => [
                    SourceItemInterface::SOURCE_CODE => $source->getSourceCode(),
                    SourceItemInterface::SKU => $simpleProductSKU,
                    SourceItemInterface::QUANTITY => 1,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ]
            ]
        );
        $this->sourceItemsSave->execute([$sourceItem]);
        $this->assertTrue(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertTrue(
            current($this->areProductsSalable->execute([$configurableProductSKU], $stock->getStockId()))->isSalable()
        );
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'src1'),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src1.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(
            DeleteSourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => 'default']
            ]
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => '$src1.source_code$', 'quantity' => 1],
            ]
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p1$']], 'opt1'),
        DataFixture(
            BundleProductFixture::class,
            ['_options' => ['$opt1$']],
            'bp1'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddBundleProductToCartFixture::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$bp1.id$',
                'selections' => [['$p1.id$']],
                'qty' => 1
            ],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(ShipmentFixture::class, ['order_id' => '$order.id$', 'items' => [['product_id' => '$bp1.id$']]]),
    ]
    public function testBundleProductShouldBeBackInStockWhenChildProductIsBackInStock(): void
    {
        $simpleProductSKU = $this->fixtures->get('p1')->getSku();
        $bundleProductSKU = $this->fixtures->get('bp1')->getSku();
        $stock = $this->fixtures->get('stk2');
        $source = $this->fixtures->get('src1');
        $this->assertFalse(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertFalse(
            current($this->areProductsSalable->execute([$bundleProductSKU], $stock->getStockId()))->isSalable()
        );
        $sourceItem = $this->sourceItemFactory->create(
            [
                'data' => [
                    SourceItemInterface::SOURCE_CODE => $source->getSourceCode(),
                    SourceItemInterface::SKU => $simpleProductSKU,
                    SourceItemInterface::QUANTITY => 1,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ]
            ]
        );
        $this->sourceItemsSave->execute([$sourceItem]);
        $this->assertTrue(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertTrue(
            current($this->areProductsSalable->execute([$bundleProductSKU], $stock->getStockId()))->isSalable()
        );
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'src1'),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src1.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(
            GroupedProductFixture::class,
            ['product_links' => [['sku' => '$p1.sku$']]],
            'gp1'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => 'default', 'quantity' => 0],
                ['sku' => '$p1.sku$', 'source_code' => '$src1.source_code$', 'quantity' => 1],
            ]
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddGroupedProductToCartFixture::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$gp1.id$',
                'child_products' => ['$p1.id$'],
            ],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(ShipmentFixture::class, ['order_id' => '$order.id$', 'items' => [['product_id' => '$p1.id$']]]),
    ]
    public function testGroupedProductShouldBeBackInStockWhenChildProductIsBackInStock(): void
    {
        $simpleProductSKU = $this->fixtures->get('p1')->getSku();
        $groupedProductSKU = $this->fixtures->get('gp1')->getSku();
        $stock = $this->fixtures->get('stk2');
        $source = $this->fixtures->get('src1');
        $this->assertFalse(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertFalse(
            current($this->areProductsSalable->execute([$groupedProductSKU], $stock->getStockId()))->isSalable()
        );
        $sourceItem = $this->sourceItemFactory->create(
            [
                'data' => [
                    SourceItemInterface::SOURCE_CODE => $source->getSourceCode(),
                    SourceItemInterface::SKU => $simpleProductSKU,
                    SourceItemInterface::QUANTITY => 1,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ]
            ]
        );
        $this->sourceItemsSave->execute([$sourceItem]);
        $this->assertTrue(
            current($this->areProductsSalable->execute([$simpleProductSKU], $stock->getStockId()))->isSalable()
        );
        $this->assertTrue(
            current($this->areProductsSalable->execute([$groupedProductSKU], $stock->getStockId()))->isSalable()
        );
    }

    /**
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
                        'product' => $product
                    ]
                ]
            );
        return $cartItem;
    }

    /**
     * @param $sku
     * @param $stockId
     */
    private function setStockItemConfigIsDecimal(string $sku, int $stockId): void
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setIsQtyDecimal(true);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);
    }

    /**
     * @param $sku
     * @param $stockId
     */
    private function setStockItemManageStockFalse(string $sku, int $stockId): void
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setManageStock(false);
        $stockItemConfiguration->setUseConfigManageStock(false);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);
    }

    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();
    }
}
