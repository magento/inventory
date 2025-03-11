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
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\DataObject;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\TestFramework\App\ApiMutableScopeConfig;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for bundle product only x left in stock
 */
class BundleProductOnlyXLeftInStockTest extends GraphQlAbstract
{
    /**
     * @var ApiMutableScopeConfig
     */
    private $scopeConfig;

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

        $this->scopeConfig = Bootstrap::getObjectManager()->get(ApiMutableScopeConfig::class);
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
     * Test only_x_left_in_stock for bundle product
     *
     * @param string $stockThresholdQty
     * @return void
     *
     * @throws \Exception
     * @dataProvider stockThresholdQtyProvider
     */
    public function testOnlyXLeftInStockBundleProduct(string $stockThresholdQty): void
    {
        $this->scopeConfig->setValue('cataloginventory/options/stock_threshold_qty', $stockThresholdQty);
        $maskedQuoteId = $this->fixtures->get('quoteIdMask')->getMaskedId();
        $query = <<<QUERY
{
	cart(cart_id: "$maskedQuoteId") {
		itemsV2 {
			items {
				product {
					only_x_left_in_stock
				}
			}
		}
	}
}
QUERY;

        $response = $this->graphQlQuery($query);
        $responseDataObject = new DataObject($response);
        $this->assertNull(
            $responseDataObject->getData('cart/itemsV2/items/0/product/only_x_left_in_stock'),
        );
    }

    /**
     * Data provider for testing only_x_left_in_stock for bundle product
     *
     * @return array[]
     */
    public function stockThresholdQtyProvider(): array
    {
        return [
            ['0'],
            ['200']
        ];
    }
}
