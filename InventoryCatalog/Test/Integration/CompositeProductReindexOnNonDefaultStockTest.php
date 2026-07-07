<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\Indexer\Test\Fixture\Indexer as IndexerFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Fixture\ScopeFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that composite product salability on non-default stocks is
 * updated when stock status is changed via StockItemRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompositeProductReindexOnNonDefaultStockTest extends TestCase
{
    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var GetStockItemData
     */
    private GetStockItemData $getStockItemData;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->stockRegistry = $objectManager->get(StockRegistryInterface::class);
        $this->getStockItemData = $objectManager->get(GetStockItemData::class);
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_bundle'], 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-bundle', 'website_ids' => ['1', '$website2.id$']],
            'child'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$child.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1]]
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$child.sku$', 'qty' => 1], 'link'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link$'], 'required' => true], 'opt'),
        DataFixture(
            BundleProductFixture::class,
            [
                'sku' => 'bundle-parent',
                'website_ids' => ['1', '$website2.id$'],
                '_options' => ['$opt$'],
                'shipment_type' => 1,
            ],
            'parent_product'
        ),
    ]
    public function testShouldReindexBundleProductStockStatusAfterSave(): void
    {
        $this->performAssertions();
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_grouped'], 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, as: 'child'),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$child.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1]]
        ),
        DataFixture(
            GroupedProductFixture::class,
            ['product_links' => ['$child.sku$']],
            'parent_product'
        ),
        DataFixture(IndexerFixture::class),
    ]
    public function testShouldReindexGroupedProductStockStatusAfterSave(): void
    {
        $this->performAssertions();
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_configurable'], 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, as: 'child'),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$child.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1]]
        ),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'],'_links' => ['$child$']],
            'parent_product'
        ),
        DataFixture(IndexerFixture::class),
    ]
    public function testShouldReindexConfigurableProductStockStatusAfterSave(): void
    {
        $this->performAssertions();
    }

    private function performAssertions(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $stock = $fixtures->get('stock');
        $this->assertNotNull($stock, 'Non-default stock fixture must be created');
        $sku = $fixtures->get('parent_product')->getSku();

        $stockId = (int) $stock->getStockId();

        $stockItemDataBefore = $this->getStockItemData->execute($sku, $stockId);
        $this->assertNotNull(
            $stockItemDataBefore,
            $sku . ' must be indexed on the non-default stock before the legacy stock item save'
        );
        $this->assertEquals(
            1,
            (int)$stockItemDataBefore[GetStockItemDataInterface::IS_SALABLE],
            $sku . ' must be salable on non-default stock before setting is_in_stock = false'
        );

        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $stockItem->setIsInStock(false);
        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

        $stockItemDataAfter = $this->getStockItemData->execute($sku, $stockId);
        $this->assertNotNull(
            $stockItemDataAfter,
            $sku . ' stock data on the non-default stock must exist after the legacy stock item save'
        );
        $this->assertEquals(
            0,
            (int)$stockItemDataAfter[GetStockItemDataInterface::IS_SALABLE],
            $sku . ' must be marked not salable on non-default stock after admin sets is_in_stock = false'
        );

        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $stockItem->setIsInStock(true);
        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

        $stockItemDataBack = $this->getStockItemData->execute($sku, $stockId);
        $this->assertNotNull(
            $stockItemDataBack,
            $sku . ' stock data on the non-default stock must exist after restoring is_in_stock = true'
        );
        $this->assertEquals(
            1,
            (int)$stockItemDataBack[GetStockItemDataInterface::IS_SALABLE],
            $sku . ' must be salable again on non-default stock after admin sets is_in_stock = true'
        );
    }
}
