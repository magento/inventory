<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Test\Api\GetPickupLocations;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\TestCase\WebapiAbstract;

class DistanceFilterOfflineTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH = '/V1/inventory/in-store-pickup/pickup-locations';
    const SERVICE_NAME = 'inventoryInStorePickupApiGetPickupLocationsV1';
    /**#@-*/

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $searchRequestData
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchRequestData,
        array $sortedPickupLocationCodes
    ) {
        $requestData = [
            'searchRequest' => $searchRequestData
        ];

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

        $responseLocationCodes = [];
        foreach ($response['items'] as $item) {
            $responseLocationCodes[] = $item['pickup_location_code'];
        }
        AssertArrayContains::assert($sortedPickupLocationCodes, $responseLocationCodes);

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
    public function executeDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                [
                    'distanceFilter' => [
                        'country' => 'DE',
                        'postcode' => '81671',
                        'radius' => 500
                    ],
                    'scopeCode' => 'eu_website',
                ],
                ['eu-3']
            ],
            [ /* Data set #1 */
                [
                    'distanceFilter' => [
                        'country' => 'FR',
                        'region' => 'Bretagne',
                        'radius' => 1000
                    ],
                    'scopeCode' => 'eu_website',
                ],
                ['eu-1']
            ],
            [ /* Data set #2 */
                [
                    'distanceFilter' => [
                        'country' => 'FR',
                        'city' => 'Saint-Saturnin-lès-Apt',
                        'radius' => 1000],
                    'scopeCode' => 'global_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ],
                ],
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #3 */
                [
                    'distanceFilter' => [
                        'country' => 'IT',
                        'postcode' => '12022',
                        'radius' => 350],
                    'scopeCode' => 'eu_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ],
                ],
                []
            ],
            [ /* Data set #4 */
                [
                    'distanceFilter' => [
                        'country' => 'IT',
                        'postcode' => '39030',
                        'region' => 'Trentino-Alto Adige',
                        'city' => 'Rasun Di Sotto',
                        'radius' => 350],
                    'scopeCode' => 'eu_website',
                ],
                ['eu-3']
            ],
            [ /* Data set #5 */
                [
                    'distanceFilter' => [
                        'country' => 'DE',
                        'postcode' => '86559',
                        'radius' => 750],
                    'scopeCode' => 'global_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ],
                ],
                ['eu-3', 'eu-1']
            ],
            [ /* Data set #6 */
                [
                    'distanceFilter' => [
                        'country' => 'US',
                        'region' => 'Kansas',
                        'radius' => 1000],
                    'scopeCode' => 'us_website',
                ],
                ['us-1']
            ],
            [ /* Data set #7. Test with descending distance sort. */
                [
                    'distanceFilter' => [
                        'country' => 'DE',
                        'postcode' => '86559',
                        'radius' => 750],
                    'scopeCode' => 'global_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_DESC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ],
                ],
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #8. Test without distance sort. */
                [
                    'distanceFilter' => [
                        'country' => 'FR',
                        'city' => 'Saint-Saturnin-lès-Apt',
                        'radius' => 1000
                    ],
                    'scopeCode' => 'global_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => SourceInterface::CITY
                        ]
                    ],
                ],
                ['eu-3', 'eu-1']
            ],
            [ /* Data set #9. Test with multiple sorts. Distance must be in priority. */
                [
                    'distanceFilter' => [
                        'country' => 'FR',
                        'city' => 'Saint-Saturnin-lès-Apt',
                        'radius' => 1000],
                    'scopeCode' => 'global_website',
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => SourceInterface::CITY
                        ],
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_ASC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ],
                ],
                ['eu-1', 'eu-3']
            ],
        ];
    }
}
