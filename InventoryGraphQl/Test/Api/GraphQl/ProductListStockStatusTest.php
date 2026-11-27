<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGraphQl\Test\Api\GraphQl;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Model\Configuration as InventoryConfiguration;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Indexer\Model\Processor as IndexerProcessor;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test stock_status of several products of different types returned by one products query
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductListStockStatusTest extends GraphQlAbstract
{
    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple-in']),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple-out', 'stock_item' => ['is_in_stock' => false, 'qty' => 0]]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'selection-in'], 'selection_in_product'),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'selection-out', 'stock_item' => ['is_in_stock' => false, 'qty' => 0]],
            'selection_out_product'
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$selection_in_product.sku$'], 'selection_in'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$selection_out_product.sku$'], 'selection_out'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection_in$']], 'option_in'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$selection_out$']], 'option_out'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-in', '_options' => ['$option_in$']]),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-out', '_options' => ['$option_out$']]),
        DataFixture(ProductFixture::class, ['sku' => 'child-in'], 'child_in'),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-out', 'stock_item' => ['is_in_stock' => false, 'qty' => 0]],
            'child_out'
        ),
        DataFixture(AttributeFixture::class, as: 'attribute'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['sku' => 'configurable-in', '_options' => ['$attribute$'], '_links' => ['$child_in$']]
        ),
        DataFixture(
            ConfigurableProductFixture::class,
            ['sku' => 'configurable-out', '_options' => ['$attribute$'], '_links' => ['$child_out$']]
        ),
        ConfigFixture(InventoryConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, 1),
    ]
    public function testStockStatusOfAllProductsInOneQuery(): void
    {
        // The config fixture is applied after the data fixtures, so out-of-stock products need a reindex to be listed.
        Bootstrap::getObjectManager()->get(IndexerProcessor::class)->reindexAllInvalid();

        $expected = [
            'simple-in' => 'IN_STOCK',
            'simple-out' => 'OUT_OF_STOCK',
            'bundle-in' => 'IN_STOCK',
            'bundle-out' => 'OUT_OF_STOCK',
            'configurable-in' => 'IN_STOCK',
            'configurable-out' => 'OUT_OF_STOCK',
        ];
        $skus = '"' . implode('", "', array_keys($expected)) . '"';
        $query = <<<QUERY
        {
          products(filter: {sku: {in: [$skus]}}, pageSize: 20) {
            items {
              sku
              stock_status
            }
          }
        }
        QUERY;

        $response = $this->graphQlQuery($query);

        $actual = array_column($response['products']['items'], 'stock_status', 'sku');
        ksort($actual);
        ksort($expected);
        $this->assertSame($expected, $actual);
    }
}
