<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Order;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySales\Model\ResourceModel\UpdateReservationsBySkus;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/419534/scenarios/2587535
 */
class PlaceOrderOnDefaultStockTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DefaultStockProviderInterface
     */
    protected $defaultStockProvider;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var UpdateReservationsBySkus
     */
    private $handler;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var int
     */
    private $orderIdToDelete;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /** @var ConsumerFactory */
    private $consumerFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
        $this->cartManagement = $this->objectManager->get(CartManagementInterface::class);
        $this->cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->cartItemFactory = $this->objectManager->get(CartItemInterfaceFactory::class);
        $this->defaultStockProvider = $this->objectManager->get(DefaultStockProviderInterface::class);
        $this->cleanupReservations = $this->objectManager->get(CleanupReservationsInterface::class);
        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->orderManagement = $this->objectManager->get(OrderManagementInterface::class);
        $this->getReservationsQuantity = $this->objectManager->get(GetReservationsQuantityInterface::class);
        $this->handler = $this->objectManager->get(UpdateReservationsBySkus::class);
        $this->messageEncoder = $this->objectManager->get(MessageEncoder::class);
        $this->stockRegistry = $this->objectManager->get(StockRegistryInterface::class);
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->consumerFactory = $this->objectManager->get(ConsumerFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();

        if ($this->orderIdToDelete) {
            $this->deleteOrderById((int)$this->orderIdToDelete);
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderWithInStockProduct(): void
    {
        $sku = 'SKU-1';
        $quoteItemQty = 4;

        $this->orderIdToDelete = $this->placeOrder($sku, $quoteItemQty);

        self::assertNotNull($this->orderIdToDelete);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderWithOutOffStockProduct(): void
    {
        $sku = 'SKU-1';
        $quoteItemQty = 8.5;
        self::expectException(LocalizedException::class);
        $this->orderIdToDelete = $this->placeOrder($sku, $quoteItemQty);

        self::assertNull($this->orderIdToDelete);
    }

    /**
     * @see https://studio.cucumber.io/projects/69435/test-plan/folders/735125/scenarios/4286905
     *
     * @magentoConfigFixture default/cataloginventory/options/synchronize_with_catalog 1
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testReservationUpdatedAfterSkuChanged(): void
    {
        $consumerName = 'inventory.reservations.update';
        $this->objectManager->get(ClearQueueProcessor::class)->execute($consumerName);
        $oldSku = 'SKU-1';
        $newSku = 'new-sku';

        $this->orderIdToDelete = $this->placeOrder($oldSku, 4);
        $this->updateProductSku($oldSku, $newSku);

        $this->processMessages($consumerName);
        $this->assertEmpty($this->getReservationBySku($oldSku));
        $this->assertNotEmpty($this->getReservationBySku($newSku));
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 1
     *
     * @return void
     */
    public function testPlaceOrderWithOutOffStockProductAndBackOrdersTurnedOn(): void
    {
        $sku = 'SKU-1';
        $quoteItemQty = 8.5;

        $this->orderIdToDelete = $this->placeOrder($sku, $quoteItemQty);

        self::assertNotNull($this->orderIdToDelete);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/manage_stock 0
     *
     * @return void
     */
    public function testPlaceOrderWithOutOffStockProductAndManageStockTurnedOff(): void
    {
        $sku = 'SKU-1';
        $quoteItemQty = 8;

        $this->orderIdToDelete = $this->placeOrder($sku, $quoteItemQty);

        self::assertNotNull($this->orderIdToDelete);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testPlaceOrderWithException(): void
    {
        $sku = 'SKU-2';
        $stockId = 30;
        $quoteItemQty = 2;

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $cart->setStoreId(1);

        $product = $this->productRepository->get($sku);

        /** @var CartItemInterface $cartItem */
        $cartItem =
            $this->cartItemFactory->create(
                [
                    'data' => [
                        CartItemInterface::KEY_SKU => $product->getSku(),
                        CartItemInterface::KEY_QTY => $quoteItemQty,
                        CartItemInterface::KEY_QUOTE_ID => (int)$cart->getId(),
                        'product_id' => $product->getId(),
                        'product' => $product
                    ]
                ]
            );
        $cart->addItem($cartItem);
        $cartId = $cart->getId();
        $this->cartRepository->save($cart);

        $this->orderIdToDelete = $this->cartManagement->placeOrder($cartId);
        $salableQtyBefore = $this->getReservationsQuantity->execute($sku, $stockId);

        self::expectException(\Exception::class);
        $this->cartManagement->placeOrder($cartId);

        $salableQtyAfter = $this->getReservationsQuantity->execute($sku, $stockId);
        self::assertSame($salableQtyBefore, $salableQtyAfter);
    }

    /**
     * Get cart
     *
     * @return CartInterface
     */
    protected function getCart(): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $cart->setStoreId(1);

        return $cart;
    }

    /**
     * Delete order by id
     *
     * @param int $orderId
     * @return void
     */
    protected function deleteOrderById(int $orderId): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Get cart item
     *
     * @param ProductInterface $product
     * @param float $quoteItemQty
     * @param int $cartId
     * @return CartItemInterface
     */
    protected function getCartItem(ProductInterface $product, float $quoteItemQty, int $cartId): CartItemInterface
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
     * Process messages
     *
     * @param string $consumerName
     * @return void
     */
    private function processMessages(string $consumerName): void
    {
        $consumer = $this->consumerFactory->get($consumerName);
        $consumer->process(1);
    }

    /**
     * Get product reservation by the sku
     *
     * @param string $sku
     * @return array
     */
    private function getReservationBySku(string $sku): array
    {
        $connect = $this->resource->getConnection();
        $select = $connect->select()->from('inventory_reservation')->where('sku = ?', $sku);
        $result = $connect->fetchRow($select);

        return $result ? $result : [];
    }

    /**
     * Place order
     *
     * @param string $sku
     * @param float $itemQty
     * @return int
     */
    private function placeOrder(string $sku, float $itemQty): int
    {
        $cart = $this->getCart();
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $itemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        return (int)$this->cartManagement->placeOrder($cart->getId());
    }

    /**
     * Update product sku
     *
     * @param string $sku
     * @param string $newSku
     * @return void
     */
    private function updateProductSku(string $sku, string $newSku): void
    {
        $product = $this->productRepository->get($sku);
        $product->setSku($newSku);
        $this->productRepository->save($product);
    }
}
