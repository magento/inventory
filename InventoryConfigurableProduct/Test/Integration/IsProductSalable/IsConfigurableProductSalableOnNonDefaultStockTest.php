<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\IsProductSalable;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Indexer\Test\Fixture\Indexer as IndexerFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Fixture\ScopeFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Validates configurable product salability through AreProductsSalable on non-default stocks.
 *
 * The stock index computes configurable is_salable as MAX(children.is_salable), and the base
 * GetIsQtySalable reads this directly for configurable products since source item management
 * is not allowed for the configurable product type.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsConfigurableProductSalableOnNonDefaultStockTest extends TestCase
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->areProductsSalable = Bootstrap::getObjectManager()->get(AreProductsSalableInterface::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_salable'], 'source1'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source1.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-salable-1', 'website_ids' => ['1', '$website2.id$']],
            'child1'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-salable-2', 'website_ids' => ['1', '$website2.id$']],
            'child2'
        ),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            [
                'sku' => 'configurable-salable',
                'website_ids' => ['1', '$website2.id$'],
                '_options' => ['$attr$'],
                '_links' => ['$child1$', '$child2$'],
            ],
            'configurable'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$child1.sku$', 'source_code' => '$source1.source_code$', 'quantity' => 100, 'status' => 1],
                ['sku' => '$child2.sku$', 'source_code' => '$source1.source_code$', 'quantity' => 100, 'status' => 1],
            ]
        ),
        DataFixture(IndexerFixture::class),
    ]
    public function testConfigurableIsSalableWhenChildrenAreInStock(): void
    {
        $stockId = (int) $this->fixtures->get('stock')->getStockId();
        $sku = $this->fixtures->get('configurable')->getSku();

        $results = $this->areProductsSalable->execute([$sku], $stockId);
        $result = current($results);

        self::assertTrue($result->isSalable(), 'Configurable should be salable when children are in stock');
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_oos'], 'source1'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source1.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'child-oos-1', 'website_ids' => ['1', '$website2.id$']], 'child1'),
        DataFixture(ProductFixture::class, ['sku' => 'child-oos-2', 'website_ids' => ['1', '$website2.id$']], 'child2'),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            [
                'sku' => 'configurable-oos',
                'website_ids' => ['1', '$website2.id$'],
                '_options' => ['$attr$'],
                '_links' => ['$child1$', '$child2$'],
            ],
            'configurable'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$child1.sku$', 'source_code' => '$source1.source_code$', 'quantity' => 0, 'status' => 0],
                ['sku' => '$child2.sku$', 'source_code' => '$source1.source_code$', 'quantity' => 0, 'status' => 0],
            ]
        ),
        DataFixture(IndexerFixture::class),
    ]
    public function testConfigurableIsNotSalableWhenAllChildrenOutOfStock(): void
    {
        $stockId = (int) $this->fixtures->get('stock')->getStockId();
        $sku = $this->fixtures->get('configurable')->getSku();

        $results = $this->areProductsSalable->execute([$sku], $stockId);
        $result = current($results);

        self::assertFalse(
            $result->isSalable(),
            'Configurable should not be salable when all children are out of stock'
        );
    }

    #[
        DbIsolation(false),
        DataFixture(ScopeFixture::class, ['type' => 'website', 'code' => 'base'], as: 'website1'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$'], 'store2'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_us'], 'sourceUs'),
        DataFixture(SourceFixture::class, ['source_code' => 'source_eu'], 'sourceEu'),
        DataFixture(StockFixture::class, as: 'stockUs'),
        DataFixture(StockFixture::class, as: 'stockEu'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stockUs.stock_id$', 'source_code' => '$sourceUs.source_code$'],
                ['stock_id' => '$stockEu.stock_id$', 'source_code' => '$sourceEu.source_code$'],
            ]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stockUs.stock_id$', 'sales_channels' => ['$website2.code$']]
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-per-stock-1', 'website_ids' => ['1', '$website2.id$']],
            'child1'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'child-per-stock-2', 'website_ids' => ['1', '$website2.id$']],
            'child2'
        ),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            [
                'sku' => 'configurable-per-stock',
                'website_ids' => ['1', '$website2.id$'],
                '_options' => ['$attr$'],
                '_links' => ['$child1$', '$child2$'],
            ],
            'configurable'
        ),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$child1.sku$', 'source_code' => '$sourceUs.source_code$', 'quantity' => 100, 'status' => 1],
                ['sku' => '$child2.sku$', 'source_code' => '$sourceUs.source_code$', 'quantity' => 100, 'status' => 1],
                ['sku' => '$child1.sku$', 'source_code' => '$sourceEu.source_code$', 'quantity' => 0, 'status' => 0],
                ['sku' => '$child2.sku$', 'source_code' => '$sourceEu.source_code$', 'quantity' => 0, 'status' => 0],
            ]
        ),
        DataFixture(IndexerFixture::class),
    ]
    public function testConfigurableSalabilityReflectsPerStockSourceAssignments(): void
    {
        $sku = $this->fixtures->get('configurable')->getSku();

        $stockUsId = (int) $this->fixtures->get('stockUs')->getStockId();
        $results = $this->areProductsSalable->execute([$sku], $stockUsId);
        $result = current($results);
        self::assertTrue(
            $result->isSalable(),
            'Configurable should be salable on US stock where children have stock'
        );

        $stockEuId = (int) $this->fixtures->get('stockEu')->getStockId();
        $results = $this->areProductsSalable->execute([$sku], $stockEuId);
        $result = current($results);
        self::assertFalse(
            $result->isSalable(),
            'Configurable should not be salable on EU stock where children are out of stock'
        );
    }
}
