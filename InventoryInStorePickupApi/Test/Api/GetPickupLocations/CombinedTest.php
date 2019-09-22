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

class CombinedTest extends WebapiAbstract
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
     * @param int $expectedTotalCount
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchRequestData,
        array $sortedPickupLocationCodes,
        int $expectedTotalCount
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
                        'radius' => 750,
                        'postcode' => '86559',
                        'country' => 'DE'
                    ],
                    'filterSet' => [
                        'cityFilter' => [
                            'value' => 'Kolbermoor,Mitry-Mory',
                            'conditionType' => 'in'
                        ],
                        'regionIdFilter' => [
                            'value' => '259',
                            'conditionType' => 'eq'
                        ],
                        'regionFilter' => [
                            'value' => 'Seine-et-Marne',
                            'conditionType' => 'eq'
                        ],
                    ],
                    'scopeCode' => 'global_website',
                ],
                ['eu-1'],
                1
            ],
            [ /* Data set #1 */
                [
                    'distanceFilter' => [
                        'radius' => 6371000,
                        'postcode' => '86559',
                        'country' => 'DE'
                    ],
                    'filterSet' => [
                        'nameFilter' => [
                            'value' => 'source',
                            'conditionType' => 'fulltext'
                        ],
                        'cityFilter' => [
                            'value' => 'Kolbermoor,Mitry-Mory,Burlingame',
                            'conditionType' => 'in'
                        ],
                        'countryFilter' => [
                            'value' => 'DE',
                            'conditionType' => 'neq'
                        ],
                    ],
                    'scopeCode' => 'global_website',
                    'pageSize' => 2,
                    'currentPage' => 2,
                    'sort' => [
                        [
                            SortOrder::DIRECTION => SortOrder::SORT_DESC,
                            SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                        ]
                    ]
                ],
                ['us-1', 'eu-1'],
                2
            ],
            [ /* Data set #2 */
                [
                    'distanceFilter' => [
                        'radius' => 750,
                        'postcode' => '86559',
                        'country' => 'DE'
                    ],
                    'scopeCode' => 'global_website',
                    'pageSize' => 1,
                    'currentPage' => 1,
                ],
                ['eu-1'],
                2
            ],
        ];
    }
}
