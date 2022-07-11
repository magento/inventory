<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Api;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Model\Stock;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryInStorePickupApi\Test\Fixture\Source as PickupLocationFixture;
use Magento\InventoryInStorePickupQuote\Test\Fixture\SetInStorePickup;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySales\Model\GetProductAvailableQty;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class RefundOrderTest extends WebapiAbstract
{
    private const SERVICE_REFUND_ORDER_NAME = 'salesRefundOrderV1';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var GetProductAvailableQty
     */
    private $getProductAvailableQty;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getReservationsQuantity = $this->objectManager->get(GetReservationsQuantityInterface::class);
        $this->getProductAvailableQty = $this->objectManager->get(GetProductAvailableQty::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        DataFixture('Magento_InventoryInStorePickupSalesApi::Test/_files/store_for_eu_website_store_carriers_conf.php'),
        DataFixture(PickupLocationFixture::class, as: 'src1'),
        DataFixture(PickupLocationFixture::class, as: 'src2'),
        DataFixture(PickupLocationFixture::class, as: 'src3'),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src1.source_code$'],
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src2.source_code$'],
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src3.source_code$']
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(
            ProductFixture::class,
            ['stock_item' => ['use_config_backorders' =>  0, 'backorders' => Stock::BACKORDERS_YES_NONOTIFY]],
            'product'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$product.sku$', 'source_code' => '$src1.source_code$', 'quantity' => 0],
                ['sku' => '$product.sku$', 'source_code' => '$src2.source_code$', 'quantity' => 10],
                ['sku' => '$product.sku$', 'source_code' => '$src3.source_code$', 'quantity' => 0],
            ]
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 10]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetInStorePickup::class, ['cart_id' => '$cart.id$', 'source_code' => '$src1.source_code$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$']),
    ]
    public function testRefundNonPickedUpOrder(): void
    {
        $order = $this->fixtures->get('order');
        $product = $this->fixtures->get('product');
        $stock = $this->fixtures->get('stk2');
        $orderItems = $order->getItems();
        $orderItem = current($orderItems);
        $reservationQtyBeforeRefund = $this->getReservationsQuantity->execute($product->getSku(), $stock->getStockId());
        $this->assertEquals(-10, $reservationQtyBeforeRefund);
        $availableQtyBeforeRefund = $this->getProductAvailableQty->execute($product->getSku(), $stock->getStockId());
        $this->assertEquals(10, $availableQtyBeforeRefund);
        $this->_webApiCall(
            $this->getRefundServiceData((int)$order->getEntityId()),
            [
                'orderId' => $order->getEntityId(),
                'items' => [['order_item_id' => $orderItem->getItemId(), 'qty' => 1]]
            ]
        );
        $reservationQtyAfterRefund = $this->getReservationsQuantity->execute($product->getSku(), $stock->getStockId());
        $this->assertEquals(-9, $reservationQtyAfterRefund);
        $availableQtyAfterRefund = $this->getProductAvailableQty->execute($product->getSku(), $stock->getStockId());
        $this->assertEquals(9, $availableQtyAfterRefund);
    }

    /**
     * Prepares and returns info for API service.
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    private function getRefundServiceData(int $orderId)
    {
        return [
            'rest' => [
                'resourcePath' => '/V1/order/' . $orderId . '/refund',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_REFUND_ORDER_NAME,
                'serviceVersion' => 'V1',
                'operation' => self::SERVICE_REFUND_ORDER_NAME . 'execute',
            ]
        ];
    }
}
