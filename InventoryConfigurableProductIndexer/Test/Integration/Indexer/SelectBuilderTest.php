<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Test\Integration\Indexer;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\InventoryConfigurableProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\Stock\DataProvider;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(SelectBuilder::class),
]
class SelectBuilderTest extends TestCase
{
    /**
     * @var DataProvider
     */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->dataProvider = Bootstrap::getObjectManager()->create(DataProvider::class);
    }

    #[
        DbIsolation(false),
        DataFixture('Magento_InventoryApi::Test/_files/sources.php'),
        DataFixture('Magento_InventoryApi::Test/_files/stocks.php'),
        DataFixture('Magento_InventoryApi::Test/_files/stock_source_links.php'),
        DataFixture('Magento_InventorySalesApi::Test/_files/websites_with_stores.php'),
        DataFixture('Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php'),
        DataFixture('Magento/ConfigurableProduct/_files/configurable_attribute.php'),
        DataFixture('Magento_InventoryConfigurableProductIndexer::Test/_files/product_configurable_multiple.php'),
        DataFixture('Magento_InventoryConfigurableProductIndexer::Test/_files/source_items_configurable_multiple.php'),
    ]
    public function testGetSelect(): void
    {
        $stockId = 10;
        $sku = 'configurable_1';
        $indexData = $this->dataProvider->getSiblingsData($stockId, ConfigurableType::TYPE_CODE, [$sku]);
        self::assertCount(1, $indexData);
        $row = reset($indexData);
        self::assertEquals($sku, $row['sku']);
        self::assertEquals(300, $row['quantity']);
        self::assertEquals(1, $row['is_salable']);
    }
}
