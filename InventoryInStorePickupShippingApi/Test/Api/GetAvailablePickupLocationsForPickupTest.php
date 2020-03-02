<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Test\Api;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryInStorePickupShippingApi\Api\GetAvailableLocationsForPickupInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Api functional tests coverage
 * @see \Magento\InventoryInStorePickupShippingApi\Api\GetAvailableLocationsForPickupInterface
 */
class GetAvailablePickupLocationsForPickupTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/inventory/in-store-pickup/available-locations-for-pickup';
    private const SERVICE_NAME = 'inventoryInStorePickupShippingApiGetAvailableLocationsForPickupV1';

    /**
     * @var GetAvailableLocationsForPickupInterface
     */
    private $getPickupLocation;

    public function setUp()
    {
        $this->getPickupLocation = ObjectManager::getInstance()->get(GetAvailableLocationsForPickupInterface::class);
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupShippingApi/Test/_files/source_all_pickup_locations.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     *
     * @param array $payload
     * @param array $expectedResult
     */
    public function testExecute(array $payload, array $expectedResult): void
    {
        $requestData = [
            'request' => $payload
        ];

        $response = $this->sendRequest($requestData);

        AssertArrayContains::assert($expectedResult, $response);
    }

    /**
     * [
     *      Payload[
     *          Products Info[
     *              Product[
     *                  Sku,
     *                  Extension Attributes[]
     *              ]
     *          ],
     *          Scope Type,
     *          Scope Code
     *      ],
     *      Expected Pickup Location Codes[]
     * ]
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
          [/** Data Set #0 */
              [
                 'productsInfo' => [
                     [
                         'sku' => 'SKU-1'
                     ],
                     [
                         'sku' => 'SKU-3'
                     ]
                 ],
                 'scopeType' => 'website',
                 'scopeCode' => 'eu_website'
              ],
              ['eu-2']
          ],
          [/** Data Set #1 */
              [
                  'productsInfo' => [
                      [
                          'sku' => 'SKU-1'
                      ],
                      [
                          'sku' => 'SKU-2'
                      ]
                  ],
                  'scopeType' => 'website',
                  'scopeCode' => 'eu_website'
              ],
              []
          ],
          [/** Data Set #2 */
              [
                  'productsInfo' => [
                      [
                          'sku' => 'SKU-1'
                      ]
                  ],
                  'scopeType' => 'website',
                  'scopeCode' => 'eu_website'
              ],
              ['eu-1', 'eu-2', 'eu-3']
          ],
          [/** Data Set #3 */
           [
               'productsInfo' => [
                   [
                       'sku' => 'SKU-1'
                   ],
                   [
                       'sku' => 'SKU-2'
                   ],
                   [
                       'sku' => 'SKU-3'
                   ],
               ],
               'scopeType' => 'website',
               'scopeCode' => 'eu_website'
           ],
           []
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
     */
    public function testExecuteDisabledPickupLocation(): void
    {
        $payload = [
            'request' => [
                'productsInfo' => [
                    [
                        'sku' => 'SKU-1'
                    ],
                    [
                        'sku' => 'SKU-3'
                    ]
                ],
                'scopeType' => 'website',
                'scopeCode' => 'eu_website'
            ]
        ];

        $expected = [];
        $response = $this->sendRequest($payload);

        AssertArrayContains::assert($expected, $response);
    }

    /**
     * @param array $requestData
     * @return array
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
}
