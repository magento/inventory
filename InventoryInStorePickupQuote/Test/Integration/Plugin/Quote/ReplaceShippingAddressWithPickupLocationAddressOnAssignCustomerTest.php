<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Integration\Plugin\Quote;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryInStorePickupApi\Test\Fixture\Source as PickupLocationFixture;
use Magento\InventoryInStorePickupQuote\Test\Fixture\SetInStorePickup;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ReplaceShippingAddressWithPickupLocationAddressOnAssignCustomerTest extends TestCase
{
    #[
        DbIsolation(false),
        ConfigFixture('carriers/instore/active', '1', 'store', 'default'),
        DataFixture(
            PickupLocationFixture::class,
            [
                'street' => '999 Store Str',
                'city' => 'Austin',
                'region' => 'Texas',
                'region_id' => 57,
                'country_id' => 'US',
                'postcode' => '78758',
            ],
            'src2'
        ),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src2.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$product.sku$', 'source_code' => '$src2.source_code$', 'quantity' => 100],
            ]
        ),
        DataFixture(
            Customer::class,
            [
                'addresses' => [
                    [
                        'default_shipping' => true,
                        'street' => ['000 Oak Ave', 'Apt 1'],
                        'city' => 'New York',
                        'region' => 'New York',
                        'region_id' => 43,
                        'country_id' => 'US',
                    ]
                ]
            ],
            'customer'
        ),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetInStorePickup::class, ['cart_id' => '$cart.id$', 'source_code' => '$src2.source_code$']),
    ]
    public function testShouldNotOverrideShippingAddressWithCustomerDefaultShippingAddress(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $cartRepository = ObjectManager::getInstance()->get(CartRepositoryInterface::class);
        $cart = $cartRepository->get($fixtures->get('cart')->getId());
        $customerRepository = ObjectManager::getInstance()->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById($fixtures->get('customer')->getId());
        $this->assertShippingAddress($cart);
        $cart->assignCustomerWithAddressChange($customer);
        $this->assertShippingAddress($cart);
    }

    private function assertShippingAddress(CartInterface $cart): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $this->assertNull($cart->getShippingAddress()->getCustomerAddressId());
        $this->assertEquals('0', $cart->getShippingAddress()->getSameAsBilling());
        $this->assertEquals('0', $cart->getShippingAddress()->getSaveInAddressBook());
        $this->assertEquals(['999 Store Str'], $cart->getShippingAddress()->getStreet());
        $this->assertEquals('Austin', $cart->getShippingAddress()->getCity());
        $this->assertEquals('Texas', $cart->getShippingAddress()->getRegion());
        $this->assertEquals(57, $cart->getShippingAddress()->getRegionId());
        $this->assertEquals('US', $cart->getShippingAddress()->getCountryId());
        $this->assertEquals('78758', $cart->getShippingAddress()->getPostcode());
        $this->assertEquals(
            InStorePickup::DELIVERY_METHOD,
            $cart->getShippingAddress()->getShippingMethod()
        );
        $this->assertEquals(
            $fixtures->get('src2')->getSourceCode(),
            $cart->getShippingAddress()?->getExtensionAttributes()?->getPickupLocationCode()
        );
    }
}
