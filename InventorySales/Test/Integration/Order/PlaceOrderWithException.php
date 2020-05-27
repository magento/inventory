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
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PlaceOrderWithException extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    protected function setUp()
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/quote.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testPlaceOrderWithException()
    {
        $sku = 'SKU-1';
        $quoteItemQty = 4;

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

        //set reserved order id for existing order
        $cart->setReservedOrderId('100000001');
        $this->cartRepository->save($cart);

        $orderId = $this->cartManagement->placeOrder($cart->getId());
        $this->expectException('\Exception');

        self::assertNull($orderId);

        //cleanup
        $this->deleteOrderById((int)$orderId);
    }
}
