<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryIndexer\Indexer\Stock\IndexDataProviderByStockId;
use PHPUnit\Framework\TestCase;

/**
 * Test to ensure that configurable products data provided by select builder exists in IndexDataProviderByStockId
 */
class SelectBuilderTest extends TestCase
{
    /**
     * @var IndexDataProviderByStockId
     */
    private $indexDataProviderByStockId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->indexDataProviderByStockId = Bootstrap::getObjectManager()->get(indexDataProviderByStockId::class);
    }

    // @codingStandardsIgnoreStart
    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProductIndexer::Test/_files/product_configurable_multiple.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProductIndexer::Test/_files/source_items_configurable_multiple.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @return void
     *
     * @magentoDbIsolation disabled
     */
    // @codingStandardsIgnoreEnd
    public function testConfigurableExistsInData()
    {
        $configurableFound = false;
        $result = iterator_to_array($this->indexDataProviderByStockId->execute(10));
        //check that configurable product exist in result
        foreach ($result as $item) {
            if ($item['sku'] === 'configurable_1') {
                $configurableFound = true;
            }
        }
        self::assertTrue($configurableFound);
    }
}
