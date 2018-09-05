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
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Framework\Registry;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases with cancel order on multi stock mode.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelOrderOnNotDefaultStockTest extends TestCase
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
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GetReservationsQuantity
     */
    private $getReservationsQuantity;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->registry = $objectManager->get(Registry::class);
        $this->cartManagement = $objectManager->get(CartManagementInterface::class);
        $this->cartRepository = $objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->cartItemFactory = $objectManager->get(CartItemInterfaceFactory::class);
        $this->defaultStockProvider = $objectManager->get(DefaultStockProviderInterface::class);
        $this->cleanupReservations = $objectManager->get(CleanupReservationsInterface::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->orderManagement = $objectManager->get(OrderManagementInterface::class);
        $this->getReservationsQuantity = $objectManager->get(GetReservationsQuantity::class);
        $this->getSalableQuantityDataBySku = $objectManager->get(GetSalableQuantityDataBySku::class);
        $this->dataObjectFactory = $objectManager->get(DataObjectFactory::class);
        $this->stockRepository = $objectManager->get(StockRepositoryInterface::class);
        $this->storeRepository = $objectManager->get(StoreRepositoryInterface::class);
        $this->storeManager = $objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();
    }

    /**
     * Cancel order with simple product.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testCancelOrderWithInStockSimpleProduct(): void
    {
        $sku = 'SKU-1';
        $stockId = 30;
        $quote = $this->getQuoteByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, 4, (int)$quote->getId());
        $quote->addItem($cartItem);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($sku, $stockId));

        $salableQuantityData = $this->getSalableQuantityDataBySkuAndStockName($sku, 'Global-stock');
        $this->assertEquals(4.5, $salableQuantityData['qty']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($sku, $stockId));

        $salableQuantityData = $this->getSalableQuantityDataBySkuAndStockName($sku, 'Global-stock');
        $this->assertEquals(8.5, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with configurable product.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryConfigurableProductIndexer/Test/_files/product_configurable_multiple.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryConfigurableProductIndexer/Test/_files/source_items_configurable_multiple.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testCancelOrderWithInStockConfigurableProduct(): void
    {
        $sku = 'configurable_1';
        $stockId = 20;
        $quote = $this->getQuoteByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $configurableOptions = $product->getTypeInstance()->getConfigurableOptions($product);
        $option = current(current($configurableOptions));
        $simpleSku = $option['sku'];
        $configurableOptions = $this->dataObjectFactory->create([
            'product' => $option['product_id'],
            'super_attribute' => [key($configurableOptions) => $option['value_index']],
            'qty' => 4
        ]);
        $quote->addProduct($product, $configurableOptions);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($simpleSku, $stockId));

        $salableQuantityData = $this->getSalableQuantityDataBySkuAndStockName($simpleSku, 'US-stock');
        $this->assertEquals(96, $salableQuantityData['qty']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($simpleSku, $stockId));

        $salableQuantityData = $this->getSalableQuantityDataBySkuAndStockName($simpleSku, 'US-stock');
        $this->assertEquals(100, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with grouped product.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryGroupedProduct/Test/_files/grouped_products_multiple.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryConfigurableProductIndexer/Test/_files/source_items_configurable_multiple.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     */
    public function testCancelOrderWithInStockGroupedProduct(): void
    {
        $this->markTestSkipped('Unskip after merge https://github.com/magento-engcom/msi/pull/1573');
        $sku = 'grouped_in_stock';
        $stockId = 20;
        $firstSimple = 'simple_11';
        $secondSimple = 'simple_22';
        $quote = $this->getQuoteByStockId($stockId);
        $product = $this->productRepository->get($sku);
        $groupedOptions = $this->dataObjectFactory->create(
            [
                'product' => $product->getId(),
                'super_group' => [
                    11 => 3,
                    22 => 5
                ]
            ]
        );
        $quote->addProduct($product, $groupedOptions);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-3, $this->getReservationsQuantity->execute($firstSimple, $stockId));
        $this->assertEquals(-5, $this->getReservationsQuantity->execute($secondSimple, $stockId));

        $salableQuantityDataForFirstSimple = $this->getSalableQuantityDataBySkuAndStockName(
            $firstSimple,
            'US-stock'
        );
        $this->assertEquals(97, $salableQuantityDataForFirstSimple['qty']);

        $salableQuantityDataForSecondSimple = $this->getSalableQuantityDataBySkuAndStockName(
            $secondSimple,
            'US-stock'
        );
        $this->assertEquals(95, $salableQuantityDataForSecondSimple['qty']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($firstSimple, $stockId));
        $this->assertEquals(0, $this->getReservationsQuantity->execute($secondSimple, $stockId));

        $salableQuantityDataForFirstSimple = $this->getSalableQuantityDataBySkuAndStockName(
            $firstSimple,
            'US-stock'
        );
        $this->assertEquals(100, $salableQuantityDataForFirstSimple['qty']);

        $salableQuantityDataForSecondSimple = $this->getSalableQuantityDataBySkuAndStockName(
            $secondSimple,
            'US-stock'
        );
        $this->assertEquals(100, $salableQuantityDataForSecondSimple['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Create and retrieve cart.
     *
     * @param int $stockId
     * @return CartInterface
     */
    private function getQuoteByStockId(int $stockId): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $stock = $this->stockRepository->get($stockId);
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        $storeCode = 'store_for_';
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() == SalesChannelInterface::TYPE_WEBSITE) {
                $storeCode .= $salesChannel->getCode();
                break;
            }
        }
        $store = $this->storeRepository->get($storeCode);
        $this->storeManager->setCurrentStore($storeCode);
        $cart->setStoreId($store->getId());

        return $cart;
    }

    /**
     * Delete order by id.
     *
     * @param int $orderId
     */
    private function deleteOrderById(int $orderId): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * Create and retrieve cart item.
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
                        'product' => $product
                    ]
                ]
            );

        return $cartItem;
    }

    /**
     * Return salable quantity data by sky and stock name
     *
     * @param string $sku
     * @param string $stockName
     * @return array
     */
    private function getSalableQuantityDataBySkuAndStockName(string $sku, string $stockName): array
    {
        $result = [];

        foreach ($this->getSalableQuantityDataBySku->execute($sku) as $item) {
            if ($stockName === $item['stock_name']) {
                $result = $item;
            }
        }

        return $result;
    }
}
