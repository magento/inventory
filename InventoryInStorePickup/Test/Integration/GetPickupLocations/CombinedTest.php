<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\GetPickupLocations;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocations.
 *
 * Cover usage of Combined Filters.
 */
class CombinedTest extends TestCase
{
    /**
     * @var GetPickupLocations
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    protected function setUp(): void
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilderInterface::class);
        $this->sortOrderBuilder = Bootstrap::getObjectManager()->get(SortOrderBuilder::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteDistanceFilterWithAddressFilters()
    {
        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')
            ->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setAreaRadius(750)
            ->setAreaSearchTerm('86559:DE')
            ->setCityFilter('Kolbermoor,Mitry-Mory', 'in')
            ->setRegionIdFilter('259')
            ->setRegionFilter('Seine-et-Marne')
            ->create();

        /** @var SearchResultInterface $result */
        $result = $this->getPickupLocations->execute($searchRequest);

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertEquals('eu-1', current($result->getItems())->getPickupLocationCode());
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteDistanceFilterWithGeneralFilters()
    {
        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')
            ->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setAreaRadius(750)
            ->setAreaSearchTerm('86559:DE')
            ->setNameFilter('source', 'fulltext')
            ->setPickupLocationCodeFilter('eu%', 'like')
            ->setCurrentPage(2)
            ->setPageSize(1)
            ->create();

        /** @var SearchResultInterface $result */
        $result = $this->getPickupLocations->execute($searchRequest);

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(2, $result->getTotalCount());
        $this->assertEquals('eu-3', current($result->getItems())->getPickupLocationCode());
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithAll()
    {
        $sort = $this->sortOrderBuilder->setField(AreaInterface::DISTANCE_FIELD)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')
            ->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setAreaRadius(6371000)
            ->setAreaSearchTerm('86559:DE')
            ->setNameFilter('source', 'fulltext')
            ->setCityFilter(
                'Kolbermoor,Mitry-Mory,Burlingame',
                'in'
            )->setCountryFilter('DE', 'neq')
            ->setPageSize(2)
            ->setCurrentPage(1)
            ->setSortOrders([$sort])
            ->create();

        /** @var SearchResultInterface $result */
        $result = $this->getPickupLocations->execute($searchRequest);

        $this->assertCount(2, $result->getItems());
        $this->assertEquals(2, $result->getTotalCount());
        $items = $result->getItems();
        $this->assertEquals('us-1', current($items)->getPickupLocationCode());
        $this->assertEquals('eu-1', next($items)->getPickupLocationCode());
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteDistanceFilterWithPaging()
    {
        $searchRequest = $this->searchRequestBuilder->setAreaRadius(750)
            ->setAreaSearchTerm('86559:DE')
            ->setScopeCode('global_website')
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        /** @var SearchResultInterface $result */
        $result = $this->getPickupLocations->execute($searchRequest);

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(2, $result->getTotalCount());
        $this->assertEquals('eu-1', current($result->getItems())->getPickupLocationCode());
    }
}
