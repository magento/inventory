<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\GetPickupLocations;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Sort orders without a field or a direction, as a web API request can send them.
 *
 * @see \Magento\InventoryInStorePickup\Model\GetPickupLocations
 */
class IncompleteSortOrderTest extends TestCase
{
    /**
     * @var GetPickupLocations
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    protected function setUp(): void
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilderInterface::class);
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
     *
     * @return void
     */
    public function testSortOrderWithoutFieldIsSkipped(): void
    {
        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')
            ->setSortOrders(
                [
                    new SortOrder([SortOrder::DIRECTION => SortOrder::SORT_DESC]),
                    new SortOrder([SortOrder::FIELD => '', SortOrder::DIRECTION => SortOrder::SORT_DESC]),
                ]
            )
            ->create();
        $withIncompleteSort = $this->getPickupLocationCodes($searchRequest);

        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')->create();
        $withoutSort = $this->getPickupLocationCodes($searchRequest);

        $this->assertNotEmpty($withoutSort);
        $this->assertSame($withoutSort, $withIncompleteSort);
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
     *
     * @return void
     */
    public function testDistanceSortOrderWithoutDirectionSortsDescending(): void
    {
        $searchRequest = $this->searchRequestBuilder->setScopeCode('global_website')
            ->setAreaRadius(1000)
            ->setAreaSearchTerm('Saint-Saturnin-lès-Apt:FR')
            ->setSortOrders([new SortOrder([SortOrder::FIELD => AreaInterface::DISTANCE_FIELD])])
            ->create();

        $this->assertSame(['eu-3', 'eu-1'], $this->getPickupLocationCodes($searchRequest));
    }

    /**
     * Get codes of the found pickup locations in result order.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return string[]
     */
    private function getPickupLocationCodes(SearchRequestInterface $searchRequest): array
    {
        $codes = [];
        foreach ($this->getPickupLocations->execute($searchRequest)->getItems() as $pickupLocation) {
            $codes[] = $pickupLocation->getPickupLocationCode();
        }

        return $codes;
    }
}
