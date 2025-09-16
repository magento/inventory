<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\GraphQl\InventoryQuote;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryQuoteGraphQl\Model\Cart\MergeCarts\CartQuantityValidator;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteIdMaskFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

#[
    CoversClass(CartQuantityValidator::class),
]
class MergeCartsTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    #[
        ConfigFixture('checkout/cart/cart_merge_preference', 'merge'),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple1',
                'price' => 10,
                'stock_item' => ['qty' => 10, 'use_config_backorders' => false, 'backorders' => 1],
            ],
            'simple1'
        ),
        DataFixture(
            ProductFixture::class,
            [
                'sku' => 'simple2',
                'price' => 20,
                'stock_item' => ['qty' => 10, 'use_config_manage_stock' => false, 'manage_stock' => false],
            ],
            'simple2'
        ),
        DataFixture(GuestCartFixture::class, as: 'guestQuote'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$guestQuote.id$'], 'guestQuoteIdMask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$guestQuote.id$', 'product_id' => '$simple1.id$', 'qty' => 8]
        ),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$guestQuote.id$', 'product_id' => '$simple2.id$', 'qty' => 20]
        ),
        DataFixture(CustomerFixture::class, ['email' => 'customer@example.com', 'password' => 'password'], 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], 'customerQuote'),
        DataFixture(QuoteIdMaskFixture::class, ['cart_id' => '$customerQuote.id$'], 'customerQuoteIdMask'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$customerQuote.id$', 'product_id' => '$simple1.id$', 'qty' => 7]
        ),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$customerQuote.id$', 'product_id' => '$simple2.id$', 'qty' => 5]
        ),
    ]
    public function testMergeCartsWhenStockIsNotManaged()
    {
        $guestQuoteMaskedId = $this->fixtures->get('guestQuoteIdMask')->getMaskedId();
        $customerQuoteMaskedId = $this->fixtures->get('customerQuoteIdMask')->getMaskedId();

        $query = $this->getCartMergeMutation($guestQuoteMaskedId, $customerQuoteMaskedId);
        $response = $this->graphQlMutation($query, headers: $this->getHeaderMap());
        self::assertArrayHasKey('mergeCarts', $response);
        self::assertArrayHasKey('items', $response['mergeCarts']);
        self::assertCount(2, $response['mergeCarts']['items']);

        $item1 = $response['mergeCarts']['items'][0];
        self::assertArrayHasKey('product', $item1);
        self::assertEquals('simple1', $item1['product']['sku']);
        self::assertArrayHasKey('quantity', $item1);
        self::assertEquals(15, $item1['quantity']);

        $item2 = $response['mergeCarts']['items'][1];
        self::assertArrayHasKey('product', $item2);
        self::assertEquals('simple2', $item2['product']['sku']);
        self::assertArrayHasKey('quantity', $item2);
        self::assertEquals(25, $item2['quantity']);
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * @param string $guestQuoteMaskedId
     * @param string $customerQuoteMaskedId
     * @return string
     */
    private function getCartMergeMutation(string $guestQuoteMaskedId, string $customerQuoteMaskedId): string
    {
        return <<<QUERY
mutation {
  mergeCarts(
    source_cart_id: "$guestQuoteMaskedId"
    destination_cart_id: "$customerQuoteMaskedId"
  ) {
    items {
      product {
        sku
      }
      quantity
    }
  }
}
QUERY;
    }
}
