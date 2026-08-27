<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Integration\Indexer;

use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Customer\Model\Group;
use Magento\Indexer\Test\Fixture\Indexer as IndexerFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Fixture\ScopeFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

#[
    DbIsolation(false),
    DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
    DataFixture(WebsiteFixture::class, as: 'website2'),
    DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
    DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
    DataFixture(SourceFixture::class, as: 'source2'),
    DataFixture(StockFixture::class, as: 'stock2'),
    DataFixture(
        StockSourceLinksFixture::class,
        [
            ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],
        ]
    ),
    DataFixture(
        StockSalesChannelsFixture::class,
        ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['$website2.code$']]
    ),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 10], 'product1'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 20], 'product2'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 5], 'product3'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 8], 'product4'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 5], 'product5'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 8], 'product6'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 5], 'product7'),
    DataFixture(ProductFixture::class, ['website_ids' => ['1', '$website2.id$'], 'price' => 8], 'product8'),
    DataFixture(
        ProductFixture::class,
        ['website_ids' => ['1', '$website2.id$'], 'price' => 8, 'status' => 2],
        'product9'
    ),
    DataFixture(
        SourceItemsFixture::class,
        [
            ['sku' => '$product1.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],

            ['sku' => '$product2.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product2.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],

            ['sku' => '$product3.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product3.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 0],

            ['sku' => '$product4.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product4.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 0],

            ['sku' => '$product5.sku$', 'source_code' => 'default', 'quantity' => 0],
            ['sku' => '$product5.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],

            ['sku' => '$product6.sku$', 'source_code' => 'default', 'quantity' => 0],
            ['sku' => '$product6.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],

            ['sku' => '$product7.sku$', 'source_code' => 'default', 'quantity' => 0],
            ['sku' => '$product7.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 0],

            ['sku' => '$product8.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product8.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],

            ['sku' => '$product9.sku$', 'source_code' => 'default', 'quantity' => 100],
            ['sku' => '$product9.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 100],
        ]
    ),

    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'opt1_1'),
    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product3$', '$product4$']], 'opt1_2'),
    DataFixture(
        BundleProductFixture::class,
        // ship separately to allow multiple source assignments
        ['website_ids' => ['1', '$website2.id$'], '_options' => ['$opt1_1$', '$opt1_2$'], 'shipment_type' => 1],
        'bundle1'
    ),

    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'opt2_1'),
    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product5$', '$product6$']], 'opt2_2'),
    DataFixture(
        BundleProductFixture::class,
        // ship separately to allow multiple source assignments
        ['website_ids' => ['1', '$website2.id$'], '_options' => ['$opt2_1$', '$opt2_2$'], 'shipment_type' => 1],
        'bundle2'
    ),

    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'opt3_1'),
    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product3$', '$product6$']], 'opt3_2'),
    DataFixture(
        BundleProductFixture::class,
        // ship separately to allow multiple source assignments
        ['website_ids' => ['1', '$website2.id$'], '_options' => ['$opt3_1$', '$opt3_2$'], 'shipment_type' => 1],
        'bundle3'
    ),

    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'opt4_1'),
    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product7$', '$product8$']], 'opt4_2'),
    DataFixture(
        BundleProductFixture::class,
        // ship separately to allow multiple source assignments
        ['website_ids' => ['1', '$website2.id$'], '_options' => ['$opt4_1$', '$opt4_2$'], 'shipment_type' => 1],
        'bundle4'
    ),

    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product1$', '$product2$']], 'opt5_1'),
    DataFixture(BundleOptionFixture::class, ['product_links' => ['$product7$', '$product9$']], 'opt5_2'),
    DataFixture(
        BundleProductFixture::class,
        // ship separately to allow multiple source assignments
        ['website_ids' => ['1', '$website2.id$'], '_options' => ['$opt5_1$', '$opt5_2$'], 'shipment_type' => 1],
        'bundle5'
    ),
    DataFixture(IndexerFixture::class)
]
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PriceIndexerTest extends TestCase
{
    /**
     * @var TableMaintainer|null
     */
    private ?TableMaintainer $priceIndexTableMaintainer;

    /**
     * @var DataFixtureStorage|null
     */
    private ?DataFixtureStorage $fixtures;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->priceIndexTableMaintainer = Bootstrap::getObjectManager()->create(TableMaintainer::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        Config('cataloginventory/options/show_out_of_stock', 0, 'store'),
    ]
    public function testBundleDynamicPriceWhenShowOutOfStockIsDisabled(): void
    {
        $this->assertPriceData([
            // bundle1: required option1 (product1, product2) + required option2 (product3, product4)
            // bundle1 is in stock on website1, thus the price range includes only available selections
            // bundle1 is out of stock on website2, thus no price data is expected
            'bundle1' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
                'website2' => null,
            ],
            // bundle2: required option1 (product1, product2) + required option2 (product5, product6)
            // bundle2 is out of stock on website1, thus no price data is expected
            // bundle2 is in stock on website2, thus the price range includes only available selections
            'bundle2' => [
                'website1' => null,
                'website2' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
            ],
            // bundle3: required option1 (product1, product2) + required option2 (product3, product6)
            // product6 in option2 is out of stock on website1
            // product3 in option2 is out of stock on website2
            // bundle3 is in stock on website1, thus the price range includes only available selections
            // bundle3 is in stock on website2, thus the price range includes only available selections
            'bundle3' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 25
                ],
                'website2' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
            ],
            // bundle4: required option1 (product1, product2) + required option2 (product7, product8)
            // product7 in option2 is out of stock on both websites
            // bundle4 is in stock on website1, thus the price range includes only available selections
            // bundle4 is in stock on website2, thus the price range includes only available selections
            'bundle4' => [
                'website1' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
                'website2' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
            ],
            // bundle5: required option1 (product1, product2) + required option2 (product7, product9)
            // product7 in option2 is out of stock on both websites
            // product9 in option2 is disabled on both websites
            // bundle5 is out of stock on website1, thus no price data is expected
            // bundle5 is out of stock on website2, thus no price data is expected
            'bundle5' => [
                'website1' => null,
                // This should be NULL as well, but due to a known issue with inventory indexing,
                // disabled products are not excluded from bundle stock status calculation leading
                // to incorrect price range calculation.
                'website2' => [
                    'min_price' => 10,
                    'max_price' => 20
                ],
            ],
        ]);
    }

    #[
        Config('cataloginventory/options/show_out_of_stock', 1, 'store'),
    ]
    public function testBundleDynamicPriceWhenShowOutOfStockIsEnabled(): void
    {
        $this->assertPriceData([
            // bundle1: required option1 (product1, product2) + required option2 (product3, product4)
            // bundle1 is in stock on website1, thus the price range includes only available selections
            // bundle1 is out of stock on website2, thus the price range includes all out of stock selections
            'bundle1' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
                'website2' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
            ],
            // bundle2: required option1 (product1, product2) + required option2 (product5, product6)
            // bundle2 is out of stock on website1, thus the price range includes all out of stock selections
            // bundle2 is in stock on website2, thus the price range includes only available selections
            'bundle2' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
                'website2' => [
                    'min_price' => 15,
                    'max_price' => 28
                ],
            ],
            // bundle3: required option1 (product1, product2) + required option2 (product3, product6)
            // product6 in option2 is out of stock on website1
            // product3 in option2 is out of stock on website2
            // bundle3 is in stock on website1, thus the price range includes only available selections
            // bundle3 is in stock on website2, thus the price range includes only available selections
            'bundle3' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 25
                ],
                'website2' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
            ],
            // bundle4: required option1 (product1, product2) + required option2 (product7, product8)
            // product7 in option2 is out of stock on both websites
            // bundle4 is in stock on website1, thus the price range includes only available selections
            // bundle4 is in stock on website2, thus the price range includes only available selections
            'bundle4' => [
                'website1' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
                'website2' => [
                    'min_price' => 18,
                    'max_price' => 28
                ],
            ],
            // bundle5: required option1 (product1, product2) + required option2 (product7, product9)
            // product7 in option2 is out of stock on both websites
            // product9 in option2 is disabled on both websites
            // bundle5 is out of stock on website1, thus the price range includes all out of stock selections
            // bundle5 is out of stock on website2, thus the price range includes all out of stock selections
            'bundle5' => [
                'website1' => [
                    'min_price' => 15,
                    'max_price' => 25
                ],
                // This should be [15, 25] as well, but due to a known issue with inventory indexing,
                // disabled products are not excluded from bundle stock status calculation leading
                // to incorrect price range calculation.
                'website2' => [
                    'min_price' => 10,
                    'max_price' => 20
                ],
            ],
        ]);
    }

    private function assertPriceData(array $expectedPriceData): void
    {
        $actualPriceData = [];
        foreach ($expectedPriceData as $sku => $websites) {
            $product = $this->fixtures->get($sku);
            foreach (array_keys($websites) as $websiteCode) {
                $website = $this->fixtures->get($websiteCode);
                $priceData = $this->getPriceData((int)$product->getId(), (int)$website->getId());
                $actualPriceData[$sku][$websiteCode] = $priceData
                    ? ['min_price' => (float)$priceData['min_price'], 'max_price' => (float)$priceData['max_price']]
                    : null;
            }
        }
        $this->assertEquals($expectedPriceData, $actualPriceData);
    }

    private function getPriceData(int $entityId, int $websiteId, int $customerGroupId = Group::NOT_LOGGED_IN_ID): ?array
    {
        $select = $this->priceIndexTableMaintainer->getConnection()->select();
        $select->from(
            ['price_index' => $this->priceIndexTableMaintainer->getMainTableByDimensions([])],
            ['entity_id', 'min_price', 'max_price']
        );
        $select->where("price_index.website_id = ?", $websiteId);
        $select->where("price_index.customer_group_id = ?", $customerGroupId);
        $select->where("price_index.entity_id = ?", $entityId);
        return $this->priceIndexTableMaintainer->getConnection()->fetchRow($select) ?: null;
    }
}
