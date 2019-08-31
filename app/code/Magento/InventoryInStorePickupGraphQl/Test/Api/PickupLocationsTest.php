<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Test\Api;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage for Pickup Locations GraphQl endpoint.
 */
class PickupLocationsTest extends GraphQlAbstract
{
    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    public function setUp()
    {
        $this->getPickupLocation = ObjectManager::getInstance()->get(GetPickupLocationInterface::class);
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider dataProvider
     *
     * @param string $body
     * @param array $expected
     * @param string $websiteCode
     * @param string $storeCode
     * @param int $totalCount
     * @param int $pageSize
     * @param int $currentPage
     * @param int $totalPages
     *
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function testPickupLocationsEndpoint(
        string $body,
        array $expected,
        string $websiteCode,
        string $storeCode,
        int $totalCount,
        int $pageSize,
        int $currentPage,
        int $totalPages
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

        $response = $this->graphQlQuery($request, [], '', ['Store' => $storeCode]);
        self::assertArrayHasKey('pickup_locations', $response);
        self::assertArrayHasKey('items', $response['pickup_locations']);
        self::assertArrayHasKey('page_info', $response['pickup_locations']);

        $this->comparePickupLocations($response['pickup_locations']['items'], $expected, $websiteCode);
        self::assertEquals($totalCount, $response['pickup_locations']['total_count']);
        self::assertEquals($pageSize, $response['pickup_locations']['page_info']['page_size']);
        self::assertEquals($currentPage, $response['pickup_locations']['page_info']['current_page']);
        self::assertEquals($totalPages, $response['pickup_locations']['page_info']['total_pages']);
    }

    /**
     * [
     *      GraphQl Request Body,
     *      Expected Pickup Location Codes[],
     *      Website Code,
     *      Store Code,
     *      Total Count,
     *      Page Size,
     *      Current Page,
     *      Total Pages
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        return [
            [ /* Data set #0. Without any filters. */
              'pickup_locations',
              ['eu-1', 'eu-3', 'us-1'],
              'global_website',
              'store_for_global_website',
              3,
              20,
              1,
              1
            ],
            [ /* Data set #1. Sort only. */
              'pickup_locations(
    sort: {
      postcode: DESC
      country_id: ASC
    }
  )',
              ['eu-3', 'eu-1', 'us-1'],
              'global_website',
              'store_for_global_website',
              3,
              20,
              1,
              1
            ],
            [ /* Data set #2. General filters with array value type conditions. */
              'pickup_locations(
    pickup_location_code: {in: ["eu-3", "eu-2", "eu-1"]}
    name:{in: ["EU-source-1", "EU-source-2"]}
  )',
              ['eu-1'],
              'eu_website',
              'store_for_eu_website',
              1,
              20,
              1,
              1
            ],
            [ /* Data set #3. General filters with sort. */
              'pickup_locations(
    pickup_location_code: {like: "eu%"}
    name:{like: "%source%"}
    page_size: 1
    current_page: 2
    sort:{
      country_id: DESC
    }
  )',
              ['eu-3'],
              'eu_website',
              'store_for_eu_website',
              2,
              1,
              2,
              2
            ],
            [ /* Data set #4. Address filter only. */
              'pickup_locations(
    address:{
      country_id: {neq:"FR"}
      region: {eq: "Bayern"}
      region_id:{eq: "81"}
      city: {like:"K%"}
      postcode:{in:["83059", "13100"]}
      street:{like: "%heimer%"}
    }
  )',
              ['eu-3'],
              'global_website',
              'store_for_global_website',
              1,
              20,
              1,
              1
            ],
            [ /* Data set #5. Distance Filter with paging. */
              'pickup_locations(
    distance:{
      radius: 750
      country_code: "DE"
      postcode: "86559"
    }
    page_size: 1
    current_page: 1
    sort: {distance: ASC}
  )',
              ['eu-1'],
              'global_website',
              'store_for_global_website',
              2,
              1,
              1,
              2
            ],
            [ /* Data set #6. Distance filter with General Filters. */
              'pickup_locations(
    distance:{
      radius: 750
      country_code: "DE"
      city: "Adelzhausen"
    }
    name:{like:"%source%"}
    pickup_location_code:{like:"eu%"}
    page_size: 1
    current_page: 2
  )',
              ['eu-3'],
              'global_website',
              'store_for_global_website',
              2,
              1,
              2,
              2
            ],
            [ /* Data set #7. Distance filter with Address Filter. */
              'pickup_locations(
    distance:{
      radius: 750
      country_code: "DE"
      postcode: "86559"
    }
    address:{
      city: {in: ["Kolbermoor", "Mitry-Mory"]}
      region: {eq: "Seine-et-Marne"}
      region_id: {eq: "259"}
      street: {like: "Rue%"}
    }
  )',
              ['eu-1'],
              'global_website',
              'store_for_global_website',
              1,
              20,
              1,
              1
            ],
            [ /* Data set #8. Filter by distance but without sort by distance. */
              'pickup_locations(
    distance: {
      country_code:"FR"
      city:"Saint-Saturnin-lès-Apt"
      radius: 1000
    }
    sort:{
      city: ASC
    }
  )',
              ['eu-3', 'eu-1'],
              'global_website',
              'store_for_global_website',
              2,
              20,
              1,
              1
            ],
            [ /* Data set #9. Test with all filters. */
                'pickup_locations(
    address:{
      city: {in: ["Kolbermoor", "Mitry-Mory", "Burlingame"]}
      region: {nin: ["Thüringen", "Bouches-du-Rhône"]}
      region_id: {nin: ["94", "194"]}
      postcode: {in: ["77292 CEDEX", "13100", "83059", "99098", "66413"]}
      country_id:{neq: "DE"}
    }
    distance:{
      radius: 6371000
      country_code: "DE"
      region: "Bayern"
    }
    name:{like:"%source%"}
    page_size: 2
    current_page: 1
    sort:{
        distance: DESC
        city: ASC
        pickup_location_code: ASC
        name: DESC
    }
  )',
              ['us-1', 'eu-1'],
              'global_website',
              'store_for_global_website',
              2,
              2,
              1,
              1
            ]
        ];
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @magentoDbIsolation disabled
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown geoname for  86559   ZZZ
     */
    public function testPickupLocationEndpointDistanceSearchByWrongAddress()
    {
        $query = <<<QUERY
{
pickup_locations(
    distance:{
      radius: 750
      country_code: "ZZZ"
      postcode: "86559"
    }
    page_size: 1
    current_page: 1
    sort: {distance: ASC}
  ){
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
}
QUERY;
        $this->graphQlQuery($query, [], '', ['Store' => 'store_for_global_website']);
    }

    /**
     * Compare received Pickup Locations.
     *
     * @param array $responseItems
     * @param array $expected
     * @param string $scopeCode
     *
     * @throws NoSuchEntityException
     */
    private function comparePickupLocations(array $responseItems, array $expected, string $scopeCode)
    {
        foreach ($responseItems as $key => $item) {
            $pickupLocation = $this->getPickupLocation->execute(
                $expected[$key],
                SalesChannelInterface::TYPE_WEBSITE,
                $scopeCode
            );
            $this->compareFields($pickupLocation, $item);
        }
    }

    /**
     * Compare if received Pickup Location data match to the original entity.
     *
     * @param PickupLocationInterface $pickupLocation
     * @param array $data
     */
    private function compareFields(PickupLocationInterface $pickupLocation, array $data): void
    {
        $this->assertEquals($pickupLocation->getName(), $data['name']);
        $this->assertEquals($pickupLocation->getPickupLocationCode(), $data['pickup_location_code']);
        $this->assertEquals($pickupLocation->getEmail(), $data['email']);
        $this->assertEquals($pickupLocation->getFax(), $data['fax']);
        $this->assertEquals($pickupLocation->getDescription(), $data['description']);
        $this->assertEquals($pickupLocation->getLatitude(), $data['latitude']);
        $this->assertEquals($pickupLocation->getLongitude(), $data['longitude']);
        $this->assertEquals($pickupLocation->getCountryId(), $data['country_id']);
        $this->assertEquals($pickupLocation->getRegionId(), $data['region_id']);
        $this->assertEquals($pickupLocation->getCity(), $data['city']);
        $this->assertEquals($pickupLocation->getStreet(), $data['street']);
        $this->assertEquals($pickupLocation->getPostcode(), $data['postcode']);
        $this->assertEquals($pickupLocation->getPhone(), $data['phone']);
    }
}
