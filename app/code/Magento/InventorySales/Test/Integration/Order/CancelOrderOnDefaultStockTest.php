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
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases with cancel order on single stock mode.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelOrderOnDefaultStockTest extends TestCase
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
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCancelOrderWithInStockSimpleProduct(): void
    {
        $sku = 'simple';
        $quote = $this->getQuote();
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, 4, (int)$quote->getId());
        $quote->addItem($cartItem);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($sku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($sku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(96, $salableQuantityData['qty']);
        $this->assertEquals('Default Stock', $salableQuantityData['stock_name']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($sku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($sku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(100, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with configurable product.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCancelOrderWithInStockConfigurableProduct(): void
    {
        $sku = 'configurable_in_stock';
        $quote = $this->getQuote();
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
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(96, $salableQuantityData['qty']);
        $this->assertEquals('Default Stock', $salableQuantityData['stock_name']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(100, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with bundle product with enabled "Dynamic price" field.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/default_stock_bundle_products_without_dynamic_price.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCancelOrderWithInStockBundleProductWithoutDynamicPrice(): void
    {
        $sku = 'bundle-product-in-stock';
        $simpleSku = 'simple';
        $quote = $this->getQuote();
        $product = $this->productRepository->get($sku);
        $bundleProduct = $product->getTypeInstance();
        $selectionCollection = $bundleProduct->getSelectionsCollection(
            $bundleProduct->getOptionsIds($product),
            $product
        );
        $bundleProductOptions = $bundleProduct->getOptions($product);
        $option = current($bundleProductOptions);
        $selections = array_map(
            function ($selection) {
                return $selection['selection_id'];
            },
            $selectionCollection->getData()
        );
        $bundleOptions = $this->dataObjectFactory->create(
            [
                'product' => $option->getParentId(),
                'bundle_option' => [$option->getOptionId() => [$selections]],
                'qty' => 4
            ]
        );
        $quote->addProduct($product, $bundleOptions);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(18, $salableQuantityData['qty']);
        $this->assertEquals('Default Stock', $salableQuantityData['stock_name']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(22, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with bundle product with disabled "Dynamic price" field.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/default_stock_bundle_products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCancelOrderWithInStockBundleProductWithDynamicPrice(): void
    {
        $this->markTestSkipped('Unskip after fixing https://github.com/magento-engcom/msi/issues/1591');
        $sku = 'bundle-product-in-stock';
        $simpleSku = 'simple';
        $quote = $this->getQuote();
        $product = $this->productRepository->get($sku);
        $bundleProduct = $product->getTypeInstance();
        $selectionCollection = $bundleProduct->getSelectionsCollection(
            $bundleProduct->getOptionsIds($product),
            $product
        );
        $bundleProductOptions = $bundleProduct->getOptions($product);
        $option = current($bundleProductOptions);
        $selections = array_map(
            function ($selection) {
                return $selection['selection_id'];
            },
            $selectionCollection->getData()
        );
        $bundleOptions = $this->dataObjectFactory->create(
            [
                'product' => $option->getParentId(),
                'bundle_option' => [$option->getOptionId() => [$selections]],
                'qty' => 4
            ]
        );
        $quote->addProduct($product, $bundleOptions);
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $this->assertNotNull($orderId);
        $this->assertEquals(-4, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(18, $salableQuantityData['qty']);
        $this->assertEquals('Default Stock', $salableQuantityData['stock_name']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($simpleSku, 1));

        $salableQuantity = $this->getSalableQuantityDataBySku->execute($simpleSku);
        $salableQuantityData = array_shift($salableQuantity);
        $this->assertEquals(22, $salableQuantityData['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Cancel order with grouped product.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryGroupedProduct/Test/_files/default_stock_grouped_products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testCancelOrderWithInStockGroupedProduct(): void
    {
        $sku = 'grouped_in_stock';
        $firstSimple = 'simple_11';
        $secondSimple = 'simple_22';
        $quote = $this->getQuote();
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
        $this->assertEquals(-3, $this->getReservationsQuantity->execute($firstSimple, 1));
        $this->assertEquals(-5, $this->getReservationsQuantity->execute($secondSimple, 1));

        $salableQuantityForFirstSimple = $this->getSalableQuantityDataBySku->execute($firstSimple);
        $salableQuantityDataForFirstSimple = array_shift($salableQuantityForFirstSimple);
        $this->assertEquals(97, $salableQuantityDataForFirstSimple['qty']);
        $this->assertEquals('Default Stock', $salableQuantityDataForFirstSimple['stock_name']);

        $salableQuantityForSecondSimple = $this->getSalableQuantityDataBySku->execute($secondSimple);
        $salableQuantityDataForSecondSimple = array_shift($salableQuantityForSecondSimple);
        $this->assertEquals(95, $salableQuantityDataForSecondSimple['qty']);
        $this->assertEquals('Default Stock', $salableQuantityDataForSecondSimple['stock_name']);

        $this->orderManagement->cancel($orderId);

        $this->assertEquals(0, $this->getReservationsQuantity->execute($firstSimple, 1));
        $this->assertEquals(0, $this->getReservationsQuantity->execute($secondSimple, 1));

        $salableQuantityForFirstSimple = $this->getSalableQuantityDataBySku->execute($firstSimple);
        $salableQuantityDataForFirstSimple = array_shift($salableQuantityForFirstSimple);
        $this->assertEquals(100, $salableQuantityDataForFirstSimple['qty']);

        $salableQuantityForSecondSimple = $this->getSalableQuantityDataBySku->execute($secondSimple);
        $salableQuantityDataForSecondSimple = array_shift($salableQuantityForSecondSimple);
        $this->assertEquals(100, $salableQuantityDataForSecondSimple['qty']);

        $this->deleteOrderById((int)$orderId);
    }

    /**
     * Create and retrieve cart.
     *
     * @return CartInterface
     */
    private function getQuote(): CartInterface
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
}
