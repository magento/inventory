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

class FilterSetTest extends WebapiAbstract
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
     * @param array $searchRequestData
     * @param string $salesChannelCode
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchRequestData,
        string $salesChannelCode,
        array $sortedPickupLocationCodes
    ): void {
        $requestData = [
            'searchRequest' => [
                'filterSet' => $searchRequestData,
                'scopeCode' => $salesChannelCode,
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
    public function executeDataProvider(): array
    {
        return [
            [ /* Data set #0 */
                [
                    'countryFilter' => ['value' => 'FR', 'conditionType' => 'eq']
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #1 */
                [
                    'countryFilter' => ['value' => 'DE', 'conditionType' => 'eq']
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #2 */
                [
                    'countryFilter' => ['value' => 'DE', 'conditionType' => 'neq']
                ],
                'global_website',
                ['eu-1', 'us-1']
            ],
            [ /* Data set #3 */
                [
                    'countryFilter' => ['value' => 'DE,FR', 'conditionType' => 'in']
                ],
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #4 */
                [
                    'countryFilter' => ['value' => 'DE', 'conditionType' => 'neq'],
                    'cityFilter' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq']
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #5 */
                [
                    'countryFilter' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'cityFilter' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq']
                ],
                'eu_website',
                []
            ],
            [ /* Data set #6 */
                [
                    'cityFilter' => ['value' => 'Kolbermoor', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #7 */
                [
                    'countryFilter' => ['value' => 'DE', 'conditionType' => 'eq'],
                    'cityFilter' => ['value' => 'Mitry-Mory,Kolbermoor', 'conditionType' => 'in'],
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #8 */
                [
                    'postcodeFilter' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1']
            ],
            [ /* Data set #9 */
                [
                    'countryFilter' => ['value' => 'FR', 'conditionType' => 'eq'],
                    'postcodeFilter' => ['value' => '77292 CEDEX', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #10 */
                [
                    'countryFilter' => ['value' => 'FR,DE', 'conditionType' => 'in'],
                    'postcodeFilter' => ['value' => '77292 CEDEX,83059', 'conditionType' => 'in'],
                ],
                'eu_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #11 */
                [
                    'cityFilter' => ['value' => 'Burlingame', 'conditionType' => 'eq'],
                    'postcodeFilter' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1']
            ],
            [ /* Data set #12 */
                [
                    'cityFilter' => ['value' => 'Burlingame', 'conditionType' => 'eq'],
                    'postcodeFilter' => ['value' => '66413', 'conditionType' => 'eq'],
                ],
                'eu_website',
                []
            ],
            [ /* Data set #13 */
                [
                    'streetFilter' => ['value' => 'Bloomquist Dr 100', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1']
            ],
            [ /* Data set #14 */
                [
                    'cityFilter' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                    'streetFilter' => ['value' => 'Rue Paul Vaillant Couturier 31', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #15 */
                [
                    'streetFilter' => ['value' => 'Rosenheimer%', 'conditionType' => 'like'],
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #16 */
                [
                    'postcodeFilter' => ['value' => '77292 CEDEX', 'conditionType' => 'eq'],
                    'streetFilter' => ['value' => 'Rue Paul%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1']
            ],
            [ /* Data set #17 */
                [
                    'countryFilter' => ['value' => 'US', 'conditionType' => 'neq'],
                    'cityFilter' => ['value' => 'Mitry-Mory', 'conditionType' => 'eq'],
                    'postcodeFilter' => ['value' => '77%', 'conditionType' => 'like'],
                    'streetFilter' => ['value' => 'Rue Paul%', 'conditionType' => 'like'],
                ],
                'global_website',
                ['eu-1']
            ],
            [ /* Data set #18 */
                [
                    'regionIdFilter' => ['value' => '81', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #19 */
                [
                    'regionFilter' => ['value' => 'Seine-et-Marne', 'conditionType' => 'eq'],
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #20 */
                [
                    'regionFilter' => ['value' => 'California', 'conditionType' => 'eq'],
                    'regionIdFilter' => ['value' => '12', 'conditionType' => 'eq'],
                ],
                'global_website',
                ['us-1']
            ],
            [ /* Data set #21 */
                [
                    'regionFilter' => ['value' => 'California', 'conditionType' => 'eq'],
                    'regionIdFilter' => ['value' => '94', 'conditionType' => 'eq'],
                ],
                'global_website',
                []
            ],
            [ /* Data set #22 */
                [
                    'countryFilter' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'regionFilter' => ['value' => 'Bayern', 'conditionType' => 'eq'],
                    'regionIdFilter' => ['value' => '81', 'conditionType' => 'eq'],
                    'cityFilter' => ['value' => 'K%', 'conditionType' => 'like'],
                    'postcodeFilter' => ['value' => '83059,13100', 'conditionType' => 'in'],
                    'streetFilter' => ['value' => 'heimer', 'conditionType' => 'fulltext'],
                ],
                'global_website',
                ['eu-3']
            ],
            [ /* Data set #23 */
                [
                    'pickupLocationCodeFilter' => ['value' => 'eu-1', 'conditionType' => 'eq']
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #24 */
                [
                    'pickupLocationCodeFilter' => ['value' => 'eu-1,eu-3', 'conditionType' => 'in']
                ],
                'eu_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #25 */
                [
                    'pickupLocationCodeFilter' => ['value' => 'eu%', 'conditionType' => 'like']
                ],
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #26 */
                [
                    'pickupLocationCodeFilter' => ['value' => 'u', 'conditionType' => 'fulltext']
                ],
                'global_website',
                ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #27 */
                [
                    'pickupLocationCodeFilter' => ['value' => 'eu-2', 'conditionType' => 'eq']
                ],
                'eu_website',
                []
            ],
            [ /* Data set #28 */
                [
                    'nameFilter' => ['value' => 'EU-source-1', 'conditionType' => 'eq']
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #29 */
                [
                    'nameFilter' => ['value' => 'source', 'conditionType' => 'fulltext']
                ],
                'global_website',
                ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #30 */
                [
                    'nameFilter' => ['value' => 'source', 'conditionType' => 'fulltext'],
                    'pickupLocationCodeFilter' => ['value' => 'eu%', 'conditionType' => 'like']
                ],
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #31 */
                [
                    'countryFilter' => ['value' => 'FR', 'conditionType' => 'neq'],
                    'regionFilter' => ['value' => 'Bayern', 'conditionType' => 'eq'],
                    'regionIdFilter' => ['value' => '81', 'conditionType' => 'eq'],
                    'cityFilter' => ['value' => 'K%', 'conditionType' => 'like'],
                    'postcodeFilter' => ['value' => '83059,13100', 'conditionType' => 'in'],
                    'streetFilter' => ['value' => 'heimer', 'conditionType' => 'fulltext'],
                    'nameFilter' => ['value' => 'source', 'conditionType' => 'fulltext'],
                    'pickupLocationCodeFilter' => ['value' => 'eu%', 'conditionType' => 'like']
                ],
                'global_website',
                ['eu-3']
            ],
        ];
    }
}
