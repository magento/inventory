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
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use Magento\TestFramework\Store\ExecuteInStoreContext;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class to test place order to additional stock
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderOnAdditionalStockTest extends AbstractBackendController
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var int
     */
    private $orderIdToDelete;

    /**
     * @var string
     */
    private $productSkuToDelete;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
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

        $this->_objectManager->get(CleanupReservationsInterface::class)->execute();

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
        $consumerName = 'inventory.reservations.update';
        $this->_objectManager->get(ClearQueueProcessor::class)->execute($consumerName);
        $oldSku = 'SKU-1';
        $newSku = 'new-sku';

        $this->orderIdToDelete = $this->_objectManager->get(ExecuteInStoreContext::class)->execute(
            'store_for_eu_website',
            [$this, 'placeOrder'],
            $oldSku,
            4
        );

        $this->updateProductSku($oldSku, $newSku);
        $this->productSkuToDelete = $newSku;

        $this->processMessages($consumerName);
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
        $this->_objectManager->get(OrderManagementInterface::class)->cancel($orderId);
        $orderRepository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $orderRepository->delete($orderRepository->get($orderId));
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
        $cartItem = $this->_objectManager->get(CartItemInterfaceFactory::class)->create([
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
     * Process messages
     *
     * @param string $consumerName
     * @return void
     */
    private function processMessages(string $consumerName): void
    {
        $consumerFactory = $this->_objectManager->get(ConsumerFactory::class);
        $consumer = $consumerFactory->get($consumerName);
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
        $connect = $this->_objectManager->get(ResourceConnection::class)->getConnection();
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
        $searchCriteria = $this->_objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $cart->setStoreId($this->_objectManager->get(StoreManagerInterface::class)->getStore()->getId());

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

        return (int)$this->_objectManager->get(CartManagementInterface::class)->placeOrder($cart->getId());
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
        $sourceData = $this->_objectManager->get(GetSourceItemsDataBySku::class)->execute($sku);
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
