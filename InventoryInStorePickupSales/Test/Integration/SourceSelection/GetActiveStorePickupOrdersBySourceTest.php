<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Integration\SourceSelection;

use Magento\InventoryInStorePickupSales\Model\ResourceModel\SourceSelection\GetActiveStorePickupOrdersBySource;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class GetActiveStorePickupOrdersBySourceTest extends TestCase
{
    /**
     * @var GetActiveStorePickupOrdersBySource
     */
    private $getActiveStorePickupOrdersBySource;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $this->getActiveStorePickupOrdersBySource = $om->get(GetActiveStorePickupOrdersBySource::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/place_order.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute() : void
    {
        $code = 'eu-1';
        $result = $this->getActiveStorePickupOrdersBySource->execute($code);
        $this->assertArrayHasKey('entity_id', $result[0]);
        $this->assertArrayHasKey('pickup_location_code', $result[0]);
        $this->assertEquals($code, $result[0]['pickup_location_code']);
    }
}
