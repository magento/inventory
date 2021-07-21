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
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test credit memo updates of the reservations
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditMemoUpdateReservationsTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var int
     */
    private $orderIdToDelete;

    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditMemoRepositoryInterface;

    /**
     * @var InvoiceManagementInterface
     */
    private $invoiceService;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->cartManagement = $this->objectManager->get(CartManagementInterface::class);
        $this->cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->cartItemFactory = $this->objectManager->get(CartItemInterfaceFactory::class);
        $this->cleanupReservations = $this->objectManager->get(CleanupReservationsInterface::class);
        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->orderManagement = $this->objectManager->get(OrderManagementInterface::class);
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->creditMemoFactory = $this->objectManager->create(CreditmemoFactory::class);
        $this->creditMemoRepositoryInterface = $this->objectManager->create(CreditmemoRepositoryInterface::class);
        $this->invoiceService = $this->objectManager->get(InvoiceManagementInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->orderIdToDelete) {
            $this->deleteOrderById((int)$this->orderIdToDelete);
        }

        $this->cleanupReservations->execute();

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testReservationShouldNotBeUpdatedOnCreditMemoEdit(): void
    {
        $productSKU = 'SKU-1';

        $this->orderIdToDelete = $this->placeOrder($productSKU, 4);
        $order = $this->orderRepository->get($this->orderIdToDelete);

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->setIncrementId($order->getIncrementId());

        $this->assertCount(1, $this->getReservationBySku($productSKU));

        $creditMemo = $this->creditMemoFactory->createByOrder($order);
        $this->creditMemoRepositoryInterface->save($creditMemo);
        $this->assertCount(2, $this->getReservationBySku($productSKU));

        $this->creditMemoRepositoryInterface->save($creditMemo);
        $this->assertCount(2, $this->getReservationBySku($productSKU));
    }

    /**
     * Delete order by id
     *
     * @param int $orderId
     * @return void
     */
    private function deleteOrderById(int $orderId): void
    {
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
    }

    /**
     * Get Cart item
     *
     * @param ProductInterface $product
     * @param float $quoteItemQty
     * @param int $cartId
     * @return CartItemInterface
     */
    private function getCartItem(ProductInterface $product, float $quoteItemQty, int $cartId): CartItemInterface
    {
        /** @var CartItemInterface $cartItem */
        $cartItem = $this->cartItemFactory->create([
            'data' => [
                CartItemInterface::KEY_SKU => $product->getSku(),
                CartItemInterface::KEY_QTY => $quoteItemQty,
                CartItemInterface::KEY_QUOTE_ID => $cartId,
                'product_id' => $product->getId(),
                'product' => $product,
            ],
        ]);

        return $cartItem;
    }

    /**
     * Get product reservations by the sku
     *
     * @param string $sku
     * @return array
     */
    private function getReservationBySku(string $sku): array
    {
        $connect = $this->resourceConnection->getConnection();
        $select = $connect->select()->from('inventory_reservation')->where('sku = ?', $sku);
        $result = $connect->fetchAll($select);

        return $result ?: [];
    }

    /**
     * Get cart
     *
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    private function getCart(): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $cart->setStoreId($this->storeManager->getStore()->getId());

        return $cart;
    }

    /**
     * Place order
     *
     * @param string $sku
     * @param float $itemQty
     * @return int
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws LocalizedException
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
}
