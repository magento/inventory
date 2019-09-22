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
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\TestCase\WebapiAbstract;

class GeneralTest extends WebapiAbstract
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
     *
     * @param string $salesChannelCode
     * @param array $paging
     * @param int $expectedTotalCount
     * @param array $sortOrder
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        string $salesChannelCode,
        array $paging,
        int $expectedTotalCount,
        array $sortOrder,
        array $sortedPickupLocationCodes
    ): void {
        $requestData = [
            'searchRequest' => [
                'scopeCode' => $salesChannelCode,
                'sort' => $sortOrder,
                'pageSize' => current($paging),
                'currentPage' => next($paging)
            ]
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
    public function executeDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                'global_website',
                [],
                3,
                [],
                ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #1 */
                'global_website',
                [],
                3,
                [],
                ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #2 */
                'global_website',
                [1, 1],
                3,
                [],
                ['eu-1']
            ],
            [ /* Data set #3 */
                'global_website',
                [1, 2],
                3,
                [],
                ['eu-3']
            ],
            [ /* Data set #4 */
                'global_website',
                [],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID
                    ]
                ],
                ['us-1', 'eu-1', 'eu-3']
            ],
            [ /* Data set #5 */
                'global_website',
                [],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::POSTCODE
                    ],
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_ASC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID
                    ]
                ],
                ['eu-3', 'eu-1', 'us-1']
            ],
            [ /* Data set #6 */
                'global_website',
                [1, 2],
                3,
                [
                    [
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                        SortOrder::FIELD => SourceInterface::COUNTRY_ID
                    ]
                ],
                ['eu-1']
            ],
        ];
    }
}
