<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Test\Api;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Api functional tests coverage
 *
 * @see \Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetPickupLocationsTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/inventory/in-store-pickup/pickup-locations';
    private const SERVICE_NAME = 'inventoryInStorePickupApiGetPickupLocationsV1';

    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    public function setUp(): void
    {
        $this->getPickupLocation = ObjectManager::getInstance()->get(GetPickupLocationInterface::class);
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_all_pickup_locations.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     *
     * @dataProvider executeIntersectionSearchDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @param array $searchRequestData
     * @param array $sortedPickupLocationCodes
     * @param int $expectedTotalCount
     *
     * @throws NoSuchEntityException
     */
    public function testExecuteIntersectionSearch(
        array $searchRequestData,
        array $sortedPickupLocationCodes,
        int $expectedTotalCount
    ): void {
        $payload = [
            'searchRequest' => $searchRequestData,
        ];

        $response = $this->sendRequest($payload);

        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

        $this->assertEquals($expectedTotalCount, $response['total_count']);
        $this->assertCount(count($sortedPickupLocationCodes), $response['items']);
        $this->comparePickupLocations(
            $response['items'],
            $sortedPickupLocationCodes,
            $searchRequestData['scopeCode']
        );
    }

    /**
     * [
     *      Filter Set[
     *          Filter Extension[
     *              Product Info[
     *                  Product[
     *                      Sku,
     *                      Extension Attributes[]
     *                  ]
     *              ]
     *          ]
     *      ],
     *      Sales Channel Type,
     *      Sales Channel Code,
     *      Expected Pickup Location Codes[],
     *      Total Count of Pickup Locations
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeIntersectionSearchDataProvider(): array
    {
        return [
            [
                /** Data Set #0 */
                [
                    'extensionAttributes' => [
                        'productsInfo' => [
                            [
                                'sku' => 'SKU-1',
                            ],
                            [
                                'sku' => 'SKU-3',
                            ],
                            [
                                'sku' => 'SKU-3',
                            ],
                            [
                                'sku' => 'SKU-3',
                            ],
                        ],
                    ],
                    'scopeType' => 'website',
                    'scopeCode' => 'eu_website',
                    'currentPage' => 1,
                ],
                ['eu-2'],
                1,
            ],
            [
                /** Data Set #1 */
                [
                    'extensionAttributes' => [
                        'productsInfo' => [
                            [
                                'sku' => 'SKU-1',
                            ],
                            [
                                'sku' => 'SKU-2',
                            ],
                        ],
                    ],
                    'scopeType' => 'website',
                    'scopeCode' => 'eu_website',
                    'currentPage' => 1,
                ],
                [],
                0,
            ],
            [
                /** Data Set #2 */
                [
                    'extensionAttributes' => [
                        'productsInfo' => [
                            [
                                'sku' => 'SKU-1',
                            ],
                        ],
                    ],
                    'scopeType' => 'website',
                    'scopeCode' => 'eu_website',
                    'currentPage' => 1,
                ],
                [
                    'eu-1',
                    'eu-2',
                    'eu-3',
                ],
                3,
            ],
            [
                /** Data Set #3 */
                [
                    'extensionAttributes' => [
                        'productsInfo' => [
                            [
                                'sku' => 'SKU-1',
                            ],
                            [
                                'sku' => 'SKU-2',
                            ],
                            [
                                'sku' => 'SKU-3',
                            ],
                        ],
                    ],
                    'scopeType' => 'website',
                    'scopeCode' => 'eu_website',
                    'currentPage' => 1,
                ],
                [],
                0,
            ],
        ];
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     * @throws NoSuchEntityException
     */
    public function testExecuteDisabledPickupLocations(): void
    {
        $searchRequestData = [
            'searchRequest' => [
                'extensionAttributes' => [
                    'productsInfo' => [
                        [
                            'sku' => 'SKU-1',
                        ],
                        [
                            'sku' => 'SKU-3',
                        ],
                    ],
                ],
                'scopeType' => 'website',
                'scopeCode' => 'eu_website',
                'currentPage' => 1,
            ],
        ];
        $expected = [];
        $response = $this->sendRequest($searchRequestData);
        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($expected, $responseLocationCodes);

        $this->comparePickupLocations(
            $response['items'],
            $expected,
            $searchRequestData['searchRequest']['scopeCode']
        );
    }

    /**
     * Run combined tests with multiple params/filters.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $searchRequestData
     * @param string[] $sortedPickupLocationCodes
     * @param int $expectedTotalCount
     *
     * @dataProvider executeCombinedDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @throws NoSuchEntityException
     */
    public function testExecuteCombined(
        array $searchRequestData,
        array $sortedPickupLocationCodes,
        int $expectedTotalCount
    ): void {
        $requestData = [
            'searchRequest' => $searchRequestData,
        ];

        $response = $this->sendRequest($requestData);

        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

        $this->comparePickupLocations(
            $response['items'],
            $sortedPickupLocationCodes,
            $searchRequestData['scopeCode']
        );

        $this->assertEquals($expectedTotalCount, $response['total_count']);
        $this->assertCount(count($sortedPickupLocationCodes), $response['items']);
    }

    /**
     * [
     *      Search Request Distance Filter[
     *          Country,
     *          Postcode,
     *          Region,
     *          City,
     *          Radius (in KM)
     *      ],
     *      Sales Channel Code,
     *      Sort Orders[
     *          Sort Order[
     *              Direction,
     *              Field
     *      ],
     *      Expected Pickup Location Codes[],
     *      Total Count of Pickup Locations
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeCombinedDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                [
                    'area' => [
                        'radius' => 750,
                        'searchTerm' => '86559:DE',
                    ],
                    'filters' => [
                        'city' => [
                            'value' => 'Kolbermoor,Mitry-Mory',
                            'conditionType' => 'in',
                        ],
                        'regionId' => [
                            'value' => '259',
                            'conditionType' => 'eq',
                        ],
                        'region' => [
                            'value' => 'Seine-et-Marne',
                            'conditionType' => 'eq',
                        ],
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                ],
                ['eu-1'],
                1,
            ],
            [ /* Data set #1 */
                [
                    'area' => [
                        'radius' => 6371000,
                        'searchTerm' => '86559:DE',
                    ],
                    'filters' => [
                        'name' => [
                            'value' => 'source',
                            'conditionType' => 'fulltext',
                        ],
                        'city' => [
                            'value' => 'Kolbermoor,Mitry-Mory,Burlingame',
                            'conditionType' => 'in',
                        ],
                        'country' => [
                            'value' => 'DE',
                            'conditionType' => 'neq',
                        ],
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'pageSize' => 2,
                    'currentPage' => 2,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_DESC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                ['us-1', 'eu-1'],
                2,
            ],
            [ /* Data set #2 */
                [
                    'area' => [
                        'radius' => 750,
                        'searchTerm' => '86559:DE',
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'pageSize' => 1,
                    'currentPage' => 1,
                ],
                ['eu-1'],
                2,
            ],
        ];
    }

    /**
     * Run tests on distance filter.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $searchRequestData
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeAreaOfflineDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @throws NoSuchEntityException
     */
    public function testExecuteareaOffline(
        array $searchRequestData,
        array $sortedPickupLocationCodes
    ): void {
        $requestData = [
            'searchRequest' => $searchRequestData,
        ];

        $response = $this->sendRequest($requestData);

        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

        $this->comparePickupLocations(
            $response['items'],
            $sortedPickupLocationCodes,
            $searchRequestData['scopeCode']
        );

        $this->assertEquals(count($sortedPickupLocationCodes), $response['total_count']);
        $this->assertCount(count($sortedPickupLocationCodes), $response['items']);
    }

    /**
     * [
     *      Search Request Distance Filter[
     *          Country,
     *          Postcode,
     *          Region,
     *          City,
     *          Radius (in KM)
     *      ],
     *      Sales Channel Code,
     *      Sort Orders[
     *          Sort Order[
     *              Direction,
     *              Field
     *      ],
     *      Expected Pickup Location Codes[]
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeAreaOfflineDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                [
                    'area' => [
                        'searchTerm' => '81671:DE',
                        'radius' => 500,
                    ],
                    'scopeCode' => 'eu_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                ],
                ['eu-3'],
            ],
            [ /* Data set #1 */
                [
                    'area' => [
                        'searchTerm' => 'Saint-Saturnin-lès-Apt:FR',
                        'radius' => 1000,
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #2 */
                [
                    'area' => [
                        'searchTerm' => '12022:IT',
                        'radius' => 350,
                    ],
                    'scopeCode' => 'eu_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                [],
            ],
            [ /* Data set #3 */
                [
                    'area' => [
                        'searchTerm' => '39030:IT',
                        'radius' => 350,
                    ],
                    'scopeCode' => 'eu_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                ],
                ['eu-3'],
            ],
            [ /* Data set #4 */
                [
                    'area' => [
                        'searchTerm' => '86559:DE',
                        'radius' => 750,
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                ['eu-3', 'eu-1'],
            ],
            [ /* Data set #5. Test with descending distance sort. */
                [
                    'area' => [
                        'searchTerm' => '86559:DE',
                        'radius' => 750,
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_DESC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #6. Test without distance sort. */
                [
                    'area' => [
                        'searchTerm' => 'Saint-Saturnin-lès-Apt:FR',
                        'radius' => 1000,
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => SourceInterface::CITY,
                        ],
                    ],
                ],
                ['eu-3', 'eu-1'],
            ],
            [ /* Data set #7. Test with multiple sorts. Distance must be in priority. */
                [
                    'area' => [
                        'searchTerm' => 'Saint-Saturnin-lès-Apt:FR',
                        'radius' => 1000,
                    ],
                    'scopeCode' => 'global_website',
                    'scopeType' => 'website',
                    'currentPage' => 1,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => SourceInterface::CITY,
                        ],
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        ],
                    ],
                ],
                ['eu-1', 'eu-3'],
            ],
        ];
    }

    /**
     * Run tests on filter set.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     *
     * @param array $searchRequestData
     * @param string $salesChannelCode
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeFiltersDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @throws NoSuchEntityException
     */
    public function testExecuteFilters(
        array $searchRequestData,
        string $salesChannelCode,
        array $sortedPickupLocationCodes
    ): void {
        $requestData = [
            'searchRequest' => [
                'filters' => $searchRequestData,
                'scopeCode' => $salesChannelCode,
                'scopeType' => 'website',
                'currentPage' => 1,
            ],
        ];

        $response = $this->sendRequest($requestData);

        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

        $this->comparePickupLocations(
            $response['items'],
            $sortedPickupLocationCodes,
            $salesChannelCode
        );

        $this->assertEquals(count($sortedPickupLocationCodes), $response['total_count']);
        $this->assertCount(count($sortedPickupLocationCodes), $response['items']);
    }

    /**
     * [
     *      Filter Set[
     *          Country,
     *          Region,
     *          RegionId,
     *          City,
     *          Postcode,
     *          Street,
     *          Name,
     *          PickupLocationCode
     *      ],
     *      Sales Channel Code,
     *      Expected Pickup Location Codes[]
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executefiltersDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                [
                    'country' => ['value' => 'FR', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #1 */
                [
                    'country' => ['value' => 'DE', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-3'],
            ],
            [ /* Data set #2 */
                [
                    'country' => ['value' => 'DE', 'conditionType' => 'neq'],
                ],
                'global_website',
                ['eu-1', 'us-1'],
            ],
            [ /* Data set #3 */
                [
                    'country' => ['value' => 'DE,FR', 'conditionType' => 'in'],
                ],
                'global_website',
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #4 */
                [
                    'country' => ['value' => 'DE', 'conditionType' => 'neq'],
                    'city' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #5 */
                [
                    'country' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'city' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                ],
                'eu_website',
                [],
            ],
            [ /* Data set #6 */
                [
                    'city' => ['value' => 'Kolbermoor', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-3'],
            ],
            [ /* Data set #7 */
                [
                    'country' => ['value' => 'DE', 'conditionType' => 'eq'],
                    'city' => ['value' => 'Mitry-Mory,Kolbermoor', 'conditionType' => 'in'],
                ],
                'eu_website',
                ['eu-3'],
            ],
            [ /* Data set #8 */
                [
                    'postcode' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1'],
            ],
            [ /* Data set #9 */
                [
                    'country' => ['value' => 'FR', 'conditionType' => 'eq'],
                    'postcode' => ['value' => '77292 CEDEX', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #10 */
                [
                    'country' => ['value' => 'FR,DE', 'conditionType' => 'in'],
                    'postcode' => ['value' => '77292 CEDEX,83059', 'conditionType' => 'in'],
                ],
                'eu_website',
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #11 */
                [
                    'city' => ['value' => 'Burlingame', 'conditionType' => 'eq'],
                    'postcode' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1'],
            ],
            [ /* Data set #12 */
                [
                    'city' => ['value' => 'Burlingame', 'conditionType' => 'eq'],
                    'postcode' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'eu_website',
                [],
            ],
            [ /* Data set #13 */
                [
                    'street' => ['value' => 'Bloomquist Dr 100', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1'],
            ],
            [ /* Data set #14 */
                [
                    'city' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                    'street' => ['value' => 'Rue Paul Vaillant Couturier 31', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #15 */
                [
                    'street' => ['value' => 'Rosenheimer%', 'conditionType' => 'like'],
                ],
                'eu_website',
                ['eu-3'],
            ],
            [ /* Data set #16 */
                [
                    'postcode' => ['value' => '77292 CEDEX', 'conditionType' => 'eq'],
                    'street' => ['value' => 'Rue Paul%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1'],
            ],
            [ /* Data set #17 */
                [
                    'country' => ['value' => 'US', 'conditionType' => 'neq'],
                    'city' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                    'postcode' => ['value' => '77%', 'conditionType' => 'like'],
                    'street' => ['value' => 'Rue Paul%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1'],
            ],
            [ /* Data set #18 */
                [
                    'regionId' => ['value' => '81', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-3'],
            ],
            [ /* Data set #19 */
                [
                    'region' => ['value' => 'Seine-et-Marne', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #20 */
                [
                    'region' => ['value' => 'California', 'conditionType' => 'eq'],
                    'regionId' => ['value' => '12', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1'],
            ],
            [ /* Data set #21 */
                [
                    'region' => ['value' => 'California', 'conditionType' => 'eq'],
                    'regionId' => ['value' => '94', 'conditionType' => 'eq'],
                ],
                'global_website',
                [],
            ],
            [ /* Data set #22 */
                [
                    'country' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'region' => ['value' => 'Bayern', 'conditionType' => 'eq'],
                    'regionId' => ['value' => '81', 'conditionType' => 'eq'],
                    'city' => ['value' => 'K%', 'conditionType' => 'like'],
                    'postcode' => ['value' => '83059,13100', 'conditionType' => 'in'],
                    'street' => ['value' => 'heimer', 'conditionType' => 'fulltext'],
                ],
                'global_website',
                ['eu-3'],
            ],
            [ /* Data set #23 */
                [
                    'pickupLocationCode' => ['value' => 'eu-1', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #24 */
                [
                    'pickupLocationCode' => ['value' => 'eu-1,eu-3', 'conditionType' => 'in'],
                ],
                'eu_website',
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #25 */
                [
                    'pickupLocationCode' => ['value' => 'eu%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #26 */
                [
                    'pickupLocationCode' => ['value' => 'u', 'conditionType' => 'fulltext'],
                ],
                'global_website',
                ['eu-1', 'eu-3', 'us-1'],
            ],
            [ /* Data set #27 */
                [
                    'pickupLocationCode' => ['value' => 'eu-2', 'conditionType' => 'eq'],
                ],
                'eu_website',
                [],
            ],
            [ /* Data set #28 */
                [
                    'name' => ['value' => 'EU-source-1', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1'],
            ],
            [ /* Data set #29 */
                [
                    'name' => ['value' => 'source', 'conditionType' => 'fulltext'],
                ],
                'global_website',
                ['eu-1', 'eu-3', 'us-1'],
            ],
            [ /* Data set #30 */
                [
                    'name' => ['value' => 'source', 'conditionType' => 'fulltext'],
                    'pickupLocationCode' => ['value' => 'eu%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1', 'eu-3'],
            ],
            [ /* Data set #31 */
                [
                    'country' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'region' => ['value' => 'Bayern', 'conditionType' => 'eq'],
                    'regionId' => ['value' => '81', 'conditionType' => 'eq'],
                    'city' => ['value' => 'K%', 'conditionType' => 'like'],
                    'postcode' => ['value' => '83059,13100', 'conditionType' => 'in'],
                    'street' => ['value' => 'heimer', 'conditionType' => 'fulltext'],
                    'name' => ['value' => 'source', 'conditionType' => 'fulltext'],
                    'pickupLocationCode' => ['value' => 'eu%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-3'],
            ],
        ];
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     *
     * @param string $salesChannelCode
     * @param array $paging
     * @param int $expectedTotalCount
     * @param array $sortOrder
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeGeneralDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @throws NoSuchEntityException
     */
    public function testExecuteGeneral(
        string $salesChannelCode,
        array $paging,
        int $expectedTotalCount,
        array $sortOrder,
        array $sortedPickupLocationCodes
    ): void {
        $requestData = [
            'searchRequest' => [
                'scopeCode' => $salesChannelCode,
                'scopeType' => 'website',
                'sort' => $sortOrder,
                'pageSize' => current($paging),
                'currentPage' => next($paging),
            ],
        ];

        $response = $this->sendRequest($requestData);

        $responseLocationCodes = $this->extractLocationCodesFromResponse($response);
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

        $this->comparePickupLocations(
            $response['items'],
            $sortedPickupLocationCodes,
            $salesChannelCode
        );

        self::assertEquals($expectedTotalCount, $response['total_count']);
    }

    /**
     * [
     *      Sales Channel Code,
     *      Page[
     *          Page Size,
     *          Current Page
     *      ],
     *      Expected Total Count,
     *      Sort Orders[
     *          Sort Order[
     *              Direction,
     *              Field
     *      ],
     *      Expected Pickup Location Codes[]
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeGeneralDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                'global_website',
                [],
                3,
                [],
                ['eu-1', 'eu-3', 'us-1'],
            ],
            [ /* Data set #1 */
                'global_website',
                [],
                3,
                [],
                ['eu-1', 'eu-3', 'us-1'],
            ],
            [ /* Data set #2 */
                'global_website',
                [1, 1],
                3,
                [],
                ['eu-1'],
            ],
            [ /* Data set #3 */
                'global_website',
                [1, 2],
                3,
                [],
                ['eu-3'],
            ],
            [ /* Data set #4 */
                'global_website',
                [],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID,
                    ],
                ],
                ['us-1', 'eu-1', 'eu-3'],
            ],
            [ /* Data set #5 */
                'global_website',
                [],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::POSTCODE,
                    ],
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_ASC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID,
                    ],
                ],
                ['eu-3', 'eu-1', 'us-1'],
            ],
            [ /* Data set #6 */
                'global_website',
                [1, 2],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID,
                    ],
                ],
                ['eu-1'],
            ],
        ];
    }

    /**
     * @param array $requestData
     * @return array|bool|float|int|string
     */
    private function sendRequest(array $requestData)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Execute',
            ],
        ];

        $response = (TESTS_WEB_API_ADAPTER == self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, $requestData);
        return $response;
    }

    /**
     * @param $response
     * @return array
     */
    private function extractLocationCodesFromResponse($response): array
    {
        $responseLocationCodes = [];
        foreach ($response['items'] as $item) {
            $responseLocationCodes[] = $item['pickup_location_code'];
        }
        return $responseLocationCodes;
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
        $index = 0;
        foreach ($responseItems as $item) {
            $pickupLocation = $this->getPickupLocation->execute(
                $expected[$index],
                SalesChannelInterface::TYPE_WEBSITE,
                $scopeCode
            );
            $this->compareFields($pickupLocation, $item);
            $index++;
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
        $this->assertEquals($pickupLocation->getPickupLocationCode(), $data['pickup_location_code']);
        $this->assertEquals($pickupLocation->getName(), $data['name'] ?? null);
        $this->assertEquals($pickupLocation->getEmail(), $data['email'] ?? null);
        $this->assertEquals($pickupLocation->getFax(), $data['fax'] ?? null);
        $this->assertEquals($pickupLocation->getDescription(), $data['description'] ?? null);
        $this->assertEquals($pickupLocation->getLatitude(), $data['latitude'] ?? null);
        $this->assertEquals($pickupLocation->getLongitude(), $data['longitude'] ?? null);
        $this->assertEquals($pickupLocation->getCountryId(), $data['country_id'] ?? null);
        $this->assertEquals($pickupLocation->getRegionId(), $data['region_id'] ?? null);
        $this->assertEquals($pickupLocation->getRegion(), $data['region'] ?? null);
        $this->assertEquals($pickupLocation->getCity(), $data['city'] ?? null);
        $this->assertEquals($pickupLocation->getStreet(), $data['street'] ?? null);
        $this->assertEquals($pickupLocation->getPostcode(), $data['postcode'] ?? null);
        $this->assertEquals($pickupLocation->getPhone(), $data['phone'] ?? null);
    }
}
