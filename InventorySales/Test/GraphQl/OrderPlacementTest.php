<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\GraphQl;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Indexer\Test\Fixture\Indexer as IndexerFixture;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test to verify inventory locks prevent overselling via GraphQL
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPlacementTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * Test that inventory locks prevent overselling when placing orders for limited stock
     *
     * @return void
     */
    #[
        DataFixture(CustomerFixture::class, as: 'customer1'),
        DataFixture(CustomerFixture::class, as: 'customer2'),
        DataFixture(SourceFixture::class, as: 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(
            ProductFixture::class,
            [
                'extension_attributes' => [
                    'stock_item' => [
                        'use_config_manage_stock' => false,
                        'manage_stock' => true,
                        'use_config_backorders' => false,
                        'backorders' => 0,
                        'is_in_stock' => true
                    ]
                ]
            ],
            as: 'product'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$product.sku$', 'source_code' => '$source.source_code$', 'quantity' => 2, 'status' => 1]]
        ),
        DataFixture(IndexerFixture::class),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer1.id$'], as: 'cart1'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart1.id$'], as: 'cart1Mask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart1.id$', 'product_id' => '$product.id$', 'qty' => 2]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer2.id$'], as: 'cart2'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart2.id$'], as: 'cart2Mask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart2.id$', 'product_id' => '$product.id$', 'qty' => 2]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart2.id$'])
    ]
    public function testInventoryLocksPreventOverselling(): void
    {
        $customer1 = $this->fixtures->get('customer1');
        $customer2 = $this->fixtures->get('customer2');
        $maskedCart1Id = $this->fixtures->get('cart1Mask')->getMaskedId();
        $maskedCart2Id = $this->fixtures->get('cart2Mask')->getMaskedId();

        $customer1Token = $this->customerTokenService->createCustomerAccessToken(
            $customer1->getEmail(),
            'password'
        );
        $customer2Token = $this->customerTokenService->createCustomerAccessToken(
            $customer2->getEmail(),
            'password'
        );

        // Attempt to place both orders sequentially via GraphQL
        $order1Success = true;
        $order2Success = true;

        try {
            $this->placeOrder($maskedCart1Id, $customer1Token);
        } catch (\Exception $e) {
            $order1Success = false;
        }

        try {
            $this->placeOrder($maskedCart2Id, $customer2Token);
        } catch (\Exception $e) {
            $order2Success = false;
        }

        // Verify that only ONE order succeeded (preventing overselling)
        $successCount = ($order1Success ? 1 : 0) + ($order2Success ? 1 : 0);

        $this->assertEquals(
            1,
            $successCount,
            'Only one order should succeed when attempting to order more than available quantity'
        );

        // Verify at least one order was placed
        $this->assertTrue(
            $order1Success || $order2Success,
            'At least one order should be placed successfully'
        );
    }

    /**
     * Test that multiple orders can be placed when sufficient quantity exists
     *
     * @return void
     */
    #[
        DataFixture(CustomerFixture::class, as: 'customer1'),
        DataFixture(CustomerFixture::class, as: 'customer2'),
        DataFixture(SourceFixture::class, as: 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(
            ProductFixture::class,
            [
                'extension_attributes' => [
                    'stock_item' => [
                        'use_config_manage_stock' => false,
                        'manage_stock' => true,
                        'use_config_backorders' => false,
                        'backorders' => 0,
                        'is_in_stock' => true
                    ]
                ]
            ],
            as: 'product'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$product.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1]]
        ),
        DataFixture(IndexerFixture::class),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer1.id$'], as: 'cart1'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart1.id$'], as: 'cart1Mask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart1.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer2.id$'], as: 'cart2'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart2.id$'], as: 'cart2Mask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart2.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart2.id$'])
    ]
    public function testMultipleOrdersSucceedWithSufficientQuantity(): void
    {
        $customer1 = $this->fixtures->get('customer1');
        $customer2 = $this->fixtures->get('customer2');
        $maskedCart1Id = $this->fixtures->get('cart1Mask')->getMaskedId();
        $maskedCart2Id = $this->fixtures->get('cart2Mask')->getMaskedId();

        $customer1Token = $this->customerTokenService->createCustomerAccessToken(
            $customer1->getEmail(),
            'password'
        );
        $customer2Token = $this->customerTokenService->createCustomerAccessToken(
            $customer2->getEmail(),
            'password'
        );

        // Both orders should succeed
        $response1 = $this->placeOrder($maskedCart1Id, $customer1Token);
        $response2 = $this->placeOrder($maskedCart2Id, $customer2Token);

        $this->assertArrayHasKey('placeOrder', $response1);
        $this->assertArrayHasKey('order', $response1['placeOrder']);
        $this->assertArrayHasKey('order_number', $response1['placeOrder']['order']);

        $this->assertArrayHasKey('placeOrder', $response2);
        $this->assertArrayHasKey('order', $response2['placeOrder']);
        $this->assertArrayHasKey('order_number', $response2['placeOrder']['order']);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => '4669'], as: 'product'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$cart.id$'], as: 'cartMask'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
    ]
    public function testPlaceOrderWithNumericSku(): void
    {
        $response = $this->placeOrder($this->fixtures->get('cartMask')->getMaskedId());
        $this->assertArrayHasKey('placeOrder', $response);
        $this->assertArrayHasKey('order', $response['placeOrder']);
        $this->assertArrayHasKey('order_number', $response['placeOrder']['order']);
        $this->assertNotEmpty($response['placeOrder']['order']['order_number']);
    }

    /**
     * Place order via GraphQL mutation
     *
     * @param string $maskedCartId
     * @param string|null $customerToken
     * @return array
     */
    private function placeOrder(string $maskedCartId, ?string $customerToken = null): array
    {
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedCartId"}) {
    order {
      order_number
    }
  }
}
QUERY;

        return $this->graphQlMutation(
            $query,
            [],
            '',
            $customerToken ? ['Authorization' => 'Bearer ' . $customerToken] : []
        );
    }
}
