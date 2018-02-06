<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\OrderManagement;

use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;
use Magento\Inventory\Test\Integration\Indexer\RemoveIndexData;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\Inventory\Model\CleanupReservationsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CancelOrderWithDefaultStockItemTest extends TestCase
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQtyInStock;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var RemoveIndexData
     */
    private $removeIndexData;

    /**
     * @var CleanupReservationsInterface
     */
    private $reservationCleanup;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var AddressInterfaceFactory */
    private $addressFactory;

    /** @var CartManagementInterface */
    private $cartManagement;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var CartItemInterfaceFactory */
    private $cartItemFactory;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->indexer->load(SourceItemIndexer::INDEXER_ID);
        $this->indexer->reindexAll();

        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $this->cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->get(GetProductQuantityInStockInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->addressFactory = Bootstrap::getObjectManager()->get(AddressInterfaceFactory::class);
        $this->cartItemFactory = Bootstrap::getObjectManager()->get(CartItemInterfaceFactory::class);

        $this->defaultStockProvider = Bootstrap::getObjectManager()->get(DefaultStockProviderInterface::class);
        $this->removeIndexData = Bootstrap::getObjectManager()->get(RemoveIndexData::class);
        $this->reservationCleanup = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown()
    {
        $this->removeIndexData->execute([$this->defaultStockProvider->getId()]);
        $this->reservationCleanup->execute();
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     */
    public function testCancelOrder()
    {
        $sku = 'SKU-1';
        $stockId = 1;
        $product = $this->productRepository->get($sku);

        $cartId = $this->cartManagement->createEmptyCart();
        $cart = $this->cartRepository->get($cartId);
        $cart->setCustomerEmail('admin@example.com');
        $cart->setCustomerIsGuest(true);
        $cartItem =
            $this->cartItemFactory->create(
                [
                    'data' => [
                        CartItemInterface::KEY_SKU => $product->getSku(),
                        CartItemInterface::KEY_QTY => 3.5,
                        CartItemInterface::KEY_QUOTE_ID => $cartId,
                        'product_id' => $product->getId(),
                        'product' => $product
                    ]
                ]
            );
        $cart->addItem($cartItem);
        $address = $this->addressFactory->create(
            [
                'data' => [
                    AddressInterface::KEY_COUNTRY_ID => 'US',
                    AddressInterface::KEY_REGION_ID => 15,
                    AddressInterface::KEY_LASTNAME => 'Doe',
                    AddressInterface::KEY_FIRSTNAME => 'John',
                    AddressInterface::KEY_STREET => 'example street',
                    AddressInterface::KEY_EMAIL => 'customer@example.com',
                    AddressInterface::KEY_CITY => 'example city',
                    AddressInterface::KEY_TELEPHONE => '000 0000',
                    AddressInterface::KEY_POSTCODE => 12345
                ]
            ]
        );
        $cart->setBillingAddress($address);
        $cart->setShippingAddress($address);
        $cart->getPayment()->setMethod('checkmo');
        $cart->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $cart->getShippingAddress()->setCollectShippingRates(true);
        $cart->getShippingAddress()->collectShippingRates();

        $this->cartRepository->save($cart);

        self::assertEquals(5.5, $this->getProductQtyInStock->execute($sku, $stockId));

        $orderId = $this->cartManagement->placeOrder($cartId);
        self::assertEquals(2, $this->getProductQtyInStock->execute($sku, $stockId));

        $this->orderManagement->cancel($orderId);
        self::assertEquals(5.5, $this->getProductQtyInStock->execute($sku, $stockId));
    }
}
