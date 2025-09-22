<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Inventory;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCart;
use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Model\Configuration as InventoryConfiguration;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Indexer\Model\Processor as IndexerProcessor;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\Attributes\TestWith;

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
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @dataProvider stockStatusProvider
     */
    public function testStockStatusBundleProduct(bool $inStock, string $expected): void
    {
        $product = $this->productRepository->get($this->fixtures->get('product1')->getSku());
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

    /**
     * Test stock_status for bundle product in catalog
     */
    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple1']),
        DataFixture(ProductFixture::class, ['sku' => 'simple2', 'stock_item' => ['is_in_stock' => false,'qty' => 0]]),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple1'], 'selection1'),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple2'], 'selection2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection1$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection2$']], 'opt2'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle1', '_options' => ['$opt1$']]),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle2', '_options' => ['$opt2$']]),
        ConfigFixture(InventoryConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, 1),
        TestWith(['bundle1', 'IN_STOCK']),
        TestWith(['bundle2', 'OUT_OF_STOCK']),
    ]
    public function testStockStatusInCatalog(string $sku, string $expected): void
    {
        // It's required as the config fixture is applied after data fixtures.
        // After enabling the "show out-of-stock" out-of-stock products are not in the search index before reindexing.
        $indexerProcessor = Bootstrap::getObjectManager()->get(IndexerProcessor::class);
        $indexerProcessor->reindexAllInvalid();

        $query = <<<QUERY
        {
          products(filter: {sku: {in: ["$sku"]}}) {
            items {
              sku
              stock_status
            }
          }
        }
        QUERY;
        $response = $this->graphQlQuery($query);

        $product = $response['products']['items'][0] ?? null;
        $this->assertIsArray($product);
        $this->assertEquals($sku, $product['sku']);
        $this->assertEquals($expected, $product['stock_status']);
    }
}
