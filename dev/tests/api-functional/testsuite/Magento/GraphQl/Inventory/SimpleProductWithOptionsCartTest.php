<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Inventory;

use Exception;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Test\Fixture\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\DataObject;
use Magento\GraphQl\Quote\GetCustomOptionsWithUIDForQueryBySku;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Quote\Test\Fixture\QuoteIdMask;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Product stock_status test
 */
#[
    DataFixture(GuestCart::class, as: 'quote'),
    DataFixture(QuoteIdMask::class, ['cart_id' => '$quote.id$'], 'quoteIdMask'),
    DataFixture(
        Product::class,
        [
            'sku' => 'simple1',
            'options' => [
                [
                    'title' => 'multiple option',
                    'type' => ProductCustomOptionInterface::OPTION_TYPE_MULTIPLE,
                    'is_require' => false,
                    'sort_order' => 5,
                    'values' => [
                        [
                            'title' => 'multiple option 1',
                            'price' => 10,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 1 sku',
                            'sort_order' => 1,
                        ],
                        [
                            'title' => 'multiple option 2',
                            'price' => 20,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 2 sku',
                            'sort_order' => 2,
                        ],
                    ],
                ],
                [
                    'title' => 'multiple option 2',
                    'type' => ProductCustomOptionInterface::OPTION_TYPE_MULTIPLE,
                    'is_require' => false,
                    'sort_order' => 5,
                    'values' => [
                        [
                            'title' => 'multiple option 2 - 1',
                            'price' => 10,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 2 sku 1',
                            'sort_order' => 1,
                        ],
                        [
                            'title' => 'multiple option 2 - 2',
                            'price' => 20,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 2 sku 2',
                            'sort_order' => 2,
                        ],
                    ],
                ],
                [
                    'title' => 'multiple option 3',
                    'type' => ProductCustomOptionInterface::OPTION_TYPE_MULTIPLE,
                    'is_require' => false,
                    'sort_order' => 5,
                    'values' => [
                        [
                            'title' => 'multiple option 3 - 1',
                            'price' => 10,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 3 sku 1',
                            'sort_order' => 1,
                        ],
                        [
                            'title' => 'multiple option 3 - 2',
                            'price' => 20,
                            'price_type' => 'fixed',
                            'sku' => 'multiple option 3 sku 2',
                            'sort_order' => 2,
                        ],
                    ],
                ]
            ]
        ],
        'product1'
    ),
]
class SimpleProductWithOptionsCartTest extends GraphQlAbstract
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
    }

    /**
     * Prepare product options and return
     *
     * @param string $sku
     * @param int $qty
     * @param string $maskedQuoteId
     * @return string|array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function getProductOptionsForQuery(string $sku, int $qty, string $maskedQuoteId): string|array|null
    {
        $getCustomOptionsWithIDV2ForQueryBySku = Bootstrap::getObjectManager()->get(
            GetCustomOptionsWithUIDForQueryBySku::class
        );

        $itemOptions = $getCustomOptionsWithIDV2ForQueryBySku->execute($sku);

        /* The type field is only required for assertions, it should not be present in query */
        foreach ($itemOptions['entered_options'] as &$enteredOption) {
            if (isset($enteredOption['type'])) {
                unset($enteredOption['type']);
            }
        }

        return preg_replace(
            '/"([^"]+)"\s*:\s*/',
            '$1:',
            json_encode($itemOptions)
        );
    }

    /**
     * @throws Exception
     */
    public function testSimpleProductWithCustomOptionsCartStockStatus()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $sku = 'simple1';
        $qty = 1;
        $productOptionsQuery = $this->getProductOptionsForQuery($sku, $qty, $maskedQuoteId);
        $query = $this->getAddProductsToCartMutation($maskedQuoteId, $qty, $sku, $productOptionsQuery);
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('items', $response['addProductsToCart']['cart']);
        self::assertCount($qty, $response['addProductsToCart']['cart']['items']);
        self::assertNotEmpty($response['addProductsToCart']['cart']['items'][0]['customizable_options']);
        $cartResponse = new DataObject($this->graphQlQuery($this->getCartQuery($maskedQuoteId)));
        self::assertEquals(
            'IN_STOCK',
            $cartResponse->getData('cart/itemsV2/items/0/product/stock_status')
        );
    }

    /**
     * @throws Exception
     */
    public function testSimpleProductWithCustomOptionsCartStockStatusOutOfStock()
    {
        $maskedQuoteId = DataFixtureStorageManager::getStorage()->get('quoteIdMask')->getMaskedId();
        $sku = 'simple1';
        $qty = 1;
        $productOptionsQuery = $this->getProductOptionsForQuery($sku, $qty, $maskedQuoteId);
        $query = $this->getAddProductsToCartMutation($maskedQuoteId, $qty, $sku, $productOptionsQuery);
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('items', $response['addProductsToCart']['cart']);
        self::assertCount($qty, $response['addProductsToCart']['cart']['items']);
        self::assertNotEmpty($response['addProductsToCart']['cart']['items'][0]['customizable_options']);

        /* Set product out of stock */
        /** @var ProductInterface $product */
        $product = DataFixtureStorageManager::getStorage()->get('product1');
        $stockItemData = [
            StockItemInterface::QTY => 0,
            StockItemInterface::MANAGE_STOCK => true,
            StockItemInterface::IS_IN_STOCK => false,
        ];
        $product->setQuantityAndStockStatus($stockItemData);
        $this->productRepository->save($product);

        $cartResponse = new DataObject($this->graphQlQuery($this->getCartQuery($maskedQuoteId)));
        self::assertEquals(
            'OUT_OF_STOCK',
            $cartResponse->getData('cart/itemsV2/items/0/product/stock_status')
        );
    }

    /**
     * Returns GraphQl query string
     *
     * @param string $maskedQuoteId
     * @param int $qty
     * @param string $sku
     * @param string|array|null $customizableOptions
     * @return string
     */
    private function getAddProductsToCartMutation(
        string $maskedQuoteId,
        int $qty,
        string $sku,
        string|array|null $customizableOptions = '',
    ): string {
        if ($customizableOptions) {
            $customizableOptions = trim($customizableOptions, '{}');
        }

        return <<<MUTATION
mutation {
    addProductsToCart(
        cartId: "{$maskedQuoteId}",
        cartItems: [
            {
                sku: "{$sku}"
                quantity: {$qty}
                {$customizableOptions}
            }
        ]
    ) {
      cart {
        items {
          product {
            name
            sku
          }
          ... on SimpleCartItem {
            customizable_options {
              label
              customizable_option_uid
              values {
                value
                customizable_option_value_uid
              }
            }
          }
        }
      }
      user_errors {
        code
        message
      }
    }
}
MUTATION;
    }

    /**
     * Return cart query with stock data
     *
     * @param string $maskedQuoteId
     * @return string
     */
    private function getCartQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
{
  cart(cart_id: "{$maskedQuoteId}") {
    itemsV2 {
      items {
       product {
        name
        sku
        only_x_left_in_stock
        stock_status
      }
    }
  }
}
}
QUERY;
    }
}
