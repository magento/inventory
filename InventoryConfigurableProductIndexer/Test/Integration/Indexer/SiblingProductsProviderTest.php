<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Test\Integration\Indexer;

use Magento\InventoryConfigurableProductIndexer\Indexer\SiblingProductsProvider;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SiblingProductsProviderTest extends TestCase
{
    /**
     * @var SiblingProductsProvider
     */
    private $siblingProductsProvider;

    protected function setUp(): void
    {
        $this->siblingProductsProvider = Bootstrap::getObjectManager()->create(SiblingProductsProvider::class);
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
        $skus = [];

        $indexNameBuilder = Bootstrap::getObjectManager()->get(IndexNameBuilder::class);
        $indexName = $indexNameBuilder->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string) $stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();
        $indexData = $this->siblingProductsProvider->getData($indexName);
        foreach ($indexData as $item) {
            $skus[] = $item['sku'];
        }
        $this->assertContains('configurable_1', $skus);
    }
}
