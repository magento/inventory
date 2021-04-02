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
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySales\Model\ResourceModel\UpdateReservationsBySkus;
use Magento\MysqlMq\Model\Driver\Queue;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Store\ExecuteInStoreContext;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class to test place order to additional stock
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class PlaceOrderOnAdditionalStockTest extends AbstractBackendController
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
     * @var UpdateReservationsBySkus
     */
    private $handler;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var int
     */
    private $orderIdToDelete;

    /**
     * @var string
     */
    private $productSkuToDelete;

    /**
     * @var GetSourceItemsDataBySku
     */
    private $getSourceItemsDataBySku;

    /**
     * @var ExecuteInStoreContext
     */
    private $executeInStoreContext;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storeManager = $this->_objectManager->get(StoreManagerInterface::class);
        $this->cartManagement = $this->_objectManager->get(CartManagementInterface::class);
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $this->cartItemFactory = $this->_objectManager->get(CartItemInterfaceFactory::class);
        $this->cleanupReservations = $this->_objectManager->get(CleanupReservationsInterface::class);
        $this->orderRepository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $this->orderManagement = $this->_objectManager->get(OrderManagementInterface::class);
        $this->handler = $this->_objectManager->get(UpdateReservationsBySkus::class);
        $this->messageEncoder = $this->_objectManager->get(MessageEncoder::class);
        $this->queue = $this->_objectManager->create(Queue::class, ['queueName' => 'inventory.reservations.update']);
        $this->resourceConnection = $this->_objectManager->get(ResourceConnection::class);
        $this->getSourceItemsDataBySku = $this->_objectManager->get(GetSourceItemsDataBySku::class);
        $this->executeInStoreContext = $this->_objectManager->get(ExecuteInStoreContext::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->orderIdToDelete) {
            $this->deleteOrderById((int)$this->orderIdToDelete);
        }

        if ($this->productSkuToDelete) {
            try {
                $this->productRepository->deleteById($this->productSkuToDelete);
            } catch (NoSuchEntityException $e) {
                // already deleted
            }
        }

        $this->cleanupReservations->execute();

        parent::tearDown();
    }

    /**
     * @see https://studio.cucumber.io/projects/69435/test-plan/folders/735125/scenarios/4285526
     *
     * @magentoDbIsolation disabled
     *
     * @magentoConfigFixture default/cataloginventory/options/synchronize_with_catalog 1
     *
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
     * @return void
     */
    public function testReservationUpdatedAfterSkuChanged(): void
    {
        $oldSku = 'SKU-1';
        $newSku = 'new-sku';

        $this->orderIdToDelete = $this->executeInStoreContext->execute(
            'store_for_eu_website',
            [$this, 'placeOrder'],
            $oldSku,
            4
        );

        $this->updateProductSku($oldSku, $newSku);
        $this->productSkuToDelete = $newSku;

        $this->processMessages('inventory.reservations.update');
        $this->assertEmpty($this->getReservationBySku($oldSku));
        $this->assertNotEmpty($this->getReservationBySku($newSku));
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
     * Process topic messages
     *
     * @param string $topicName
     * @return void
     */
    private function processMessages(string $topicName): void
    {
        $envelope = $this->queue->dequeue();
        $decodedMessage = $this->messageEncoder->decode($topicName, $envelope->getBody());
        $this->handler->execute($decodedMessage);
    }

    /**
     * Get product reservation by the sku
     *
     * @param string $sku
     * @return array
     */
    private function getReservationBySku(string $sku): array
    {
        $connect = $this->resourceConnection->getConnection();
        $select = $connect->select()->from('inventory_reservation')->where('sku = ?', $sku);
        $result = $connect->fetchRow($select);

        return $result ?: [];
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
        $cart->setStoreId($this->storeManager->getStore()->getId());

        return $cart;
    }

    /**
     * Place order
     *
     * @param string $sku
     * @param float $itemQty
     * @return int
     */
    public function placeOrder(string $sku, float $itemQty): int
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
        $sourceData = $this->getSourceItemsDataBySku->execute($sku);
        $postData = [
            'product' => [
                'sku' => $newSku,
            ],
            'sources' => [
                'assigned_sources' => [
                    $sourceData[0]
                ],
            ],
        ];

        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/catalog/product/save/id/' . $product->getEntityId());
    }
}
