<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Inventory;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCart;
use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\DataObject;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for bundle product stock_status
 */
class BundleProductStockStatusTest extends GraphQlAbstract
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$product1.sku$'], 'selection1'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$product2.sku$'], 'selection2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection1$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection2$']], 'opt2'),
        DataFixture(
            BundleProductFixture::class,
            [
                '_options' => ['$opt1$', '$opt2$']
            ],
            'bundle_product'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddBundleProductToCart::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$bundle_product.id$',
                'selections' => [['$product1.id$'], ['$product2.id$']]
            ]
        ),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    /**
     * Test stock_status for bundle product
     *
     * @param bool $inStock
     * @param string $expected
     * @return void
     *
     * @dataProvider stockStatusProvider
     */
    public function testStockStatusBundleProduct(bool $inStock, string $expected): void
    {
        $bundleProductSku = $this->fixtures->get('bundle_product')->getSku();
        $product = $this->productRepository->get($bundleProductSku);
        $product->getExtensionAttributes()->getStockItem()->setIsInStock($inStock);
        $this->productRepository->save($product);

        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $query = <<<QUERY
{
	cart(cart_id: "$maskedQuoteId") {
		itemsV2 {
			items {
				product {
					stock_status
				}
			}
		}
	}
}
QUERY;

        $response = $this->graphQlQuery($query);
        $responseDataObject = new DataObject($response);
        $this->assertEquals(
            $expected,
            $responseDataObject->getData('cart/itemsV2/items/0/product/stock_status'),
        );
    }

    /**
     * Data provider for testing stock_status for bundle product
     *
     * @return array[]
     */
    public function stockStatusProvider(): array
    {
        return [
            [true, 'IN_STOCK'],
            [false, 'OUT_OF_STOCK']
        ];
    }
}
