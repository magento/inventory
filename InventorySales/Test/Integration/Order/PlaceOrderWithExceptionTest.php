<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PlaceOrderWithExceptionTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->cartItemFactory = Bootstrap::getObjectManager()->get(CartItemInterfaceFactory::class);
        $this->cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
        $this->getReservationsQuantity = Bootstrap::getObjectManager()->get(GetReservationsQuantityInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testPlaceOrderWithException()
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

        $orderId = $this->cartManagement->placeOrder($cartId);
        $salableQtyBefore = $this->getReservationsQuantity->execute($sku, $stockId);

        self::expectException(\Exception::class);
        $this->cartManagement->placeOrder($cartId);

        $salableQtyAfter = $this->getReservationsQuantity->execute($sku, $stockId);
        self::assertSame($salableQtyBefore, $salableQtyAfter);

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        /** @var OrderManagementInterface $orderManagement */
        $orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);

        $orderManagement->cancel($orderId);
        $orderRepository->delete($orderRepository->get($orderId));
    }
}
