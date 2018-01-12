<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\OrderManagement;

use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Address;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;
use Magento\Inventory\Test\Integration\Indexer\RemoveIndexData;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\Inventory\Model\CleanupReservationsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderAddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;


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

    /** @var OrderItemInterfaceFactory */
    private $orderItemFactory;

    /** @var OrderPaymentInterfaceFactory */
    private $orderPaymentFactory;

    /** @var OrderAddressInterfaceFactory */
    private $addressFactory;

    /** @var StoreRepositoryInterface */
    private $storeRepository;

    /** @var OrderInterfaceFactory */
    private $orderFactory;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->indexer->load(SourceItemIndexer::INDEXER_ID);
        $this->indexer->reindexAll();

        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->get(GetProductQuantityInStockInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->orderItemFactory = Bootstrap::getObjectManager()->get(OrderItemInterfaceFactory::class);
        $this->orderPaymentFactory = Bootstrap::getObjectManager()->get(OrderPaymentInterfaceFactory::class);
        $this->addressFactory = Bootstrap::getObjectManager()->get(OrderAddressInterfaceFactory::class);
        $this->storeRepository = Bootstrap::getObjectManager()->get(StoreRepositoryInterface::class);
        $this->orderFactory = Bootstrap::getObjectManager()->get(OrderInterfaceFactory::class);

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
        $orderId = 1;
        $sku = 'SKU-1';
        $stockId = 1;

        $product = $this->productRepository->get($sku);
        $orderItems = [
            $this->orderItemFactory->create(
                [
                    'data' => [
                        OrderItemInterface::SKU => $product->getSku(),
                        OrderItemInterface::PRODUCT_ID => $product->getId(),
                        OrderItemInterface::ITEM_ID => 1,
                        OrderItemInterface::ORDER_ID => $orderId,
                        OrderItemInterface::QTY_ORDERED => 3.5,
                    ]
                ]
            )
        ];
        $payment = $this->orderPaymentFactory->create(
            [
                'data' => [
                    OrderPaymentInterface::ENTITY_ID => 1,
                    OrderPaymentInterface::METHOD => 'free'
                ]
            ]
        );
        $address = $this->addressFactory->create(
            [
                'data' => [
                    OrderAddressInterface::ENTITY_ID => 1,
                    OrderAddressInterface::COUNTRY_ID => 1,
                    OrderAddressInterface::LASTNAME => 'Doe',
                    OrderAddressInterface::FIRSTNAME => 'John',
                    OrderAddressInterface::STREET => 'example street',
                    OrderAddressInterface::EMAIL => 'customer@example.com',
                    OrderAddressInterface::CITY => 'example city',
                    OrderAddressInterface::TELEPHONE => '000 0000',
                    OrderAddressInterface::ADDRESS_TYPE => Address::TYPE_BILLING,
                    'postcode' => 12345
                ]
            ]
        );
        /** @var \Magento\Store\Api\Data\StoreInterface $store */
        $store = $this->storeRepository->get('default');
        $orderData = [
            OrderInterface::ENTITY_ID => 1,
            OrderInterface::STATE => 'pending',
            OrderInterface::STATUS => 'pending',
            OrderInterface::ITEMS => $orderItems,
            OrderInterface::PAYMENT => $payment,
            OrderInterface::STORE_ID => $store->getId(),
            OrderInterface::BILLING_ADDRESS => $address,
            'addresses' => [$address]
        ];
        $order = $this->orderFactory->create(['data' => $orderData]);

        self::assertEquals(5.5, $this->getProductQtyInStock->execute($sku, $stockId));

        $this->orderManagement->place($order);
        self::assertEquals(2, $this->getProductQtyInStock->execute($sku, $stockId));

        $this->orderManagement->cancel($orderId);
        self::assertEquals(5.5, $this->getProductQtyInStock->execute($sku, $stockId));
    }
}
