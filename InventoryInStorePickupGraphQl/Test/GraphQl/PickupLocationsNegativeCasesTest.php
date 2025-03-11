<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Test\GraphQl;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage for Pickup Locations GraphQl endpoint.
 * Test negative test cases.
 */
class PickupLocationsNegativeCasesTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoApiDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider dataProvider
     *
     * @param string $body
     * @param string $storeCode
     * @param string|null $expectedExceptionMessage
     *
     * @throws \Exception
     */
    public function testPickupLocationsEndpoint(
        string $body,
        string $storeCode,
        ?string $expectedExceptionMessage = null
    ) {
        $responseTemplate = <<<QUERY
{
    items {
      pickup_location_code
      name
      email
      fax
      description
      latitude
      longitude
      country_id
      region_id
      region
      city
      street
      postcode
      phone
    },
    total_count
    page_info {
      page_size
      current_page
      total_pages
    }
  }
QUERY;

        $request = '{' . PHP_EOL . $body . $responseTemplate . PHP_EOL . '}';

        $this->expectException(\Exception::class);
        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->graphQlQuery($request, [], '', ['Store' => $storeCode]);
    }

    /**
     * [
     *      GraphQl Request Body,
     *      Store Code,
     *      Exception Message,
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function dataProvider(): array
    {
        /* Data set #0. Search by not existing address */
        return [
            [/* Data set #0. Search by not existing address */
                'pickupLocations(
    area:{
      radius: 750
      search_term: "86559:ZZ"
    }
    pageSize: 1
    currentPage: 1
    sort: {distance: ASC}
  )',
                'store_for_global_website',
            ],
            [/* Data set #1. Wrong page size. */
                'pickupLocations(
    pageSize: -1
    currentPage: 1
  )',
                'store_for_global_website',
                'pageSize value must be greater than 0.',
            ],
            [/* Data set #2. Wrong current page. */
                'pickupLocations(
    pageSize: 10
    currentPage: -1
  )',
                'store_for_global_website',
                'currentPage value must be greater than 0.',
            ],
            [/* Data set #3. Wrong max page. */
                'pickupLocations(
    area:{
      radius: 750
      search_term: "86559:DE"
    }
    pageSize: 1
    currentPage: 4
    sort: {distance: ASC}
  )',
                'store_for_global_website',
                'currentPage value 4 specified is greater than the 2 page(s) available.',
            ],
        ];
    }
}
