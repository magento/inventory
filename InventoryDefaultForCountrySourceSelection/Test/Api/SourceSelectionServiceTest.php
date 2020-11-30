<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Algorithms\DefaultForCountryAlgorithm;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Source\InitCountriesSelectionExtensionAttributes;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test default_for_countries algorithm
 */
class SourceSelectionServiceTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH = '/V1/inventory/source-selection-algorithm-result';
    const SERVICE_NAME = 'inventorySourceSelectionApiSourceSelectionServiceV1';
    /**#@-*/

    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $defaultAlgorithmCode;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    /**
     * @magentoConfigFixture cataloginventory/source_selection_default_for_country/additional_algorithm priority
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items_eu_stock_only.php
     */
    public function testSourceSelectionService()
    {
        $countriesToTest = [
            'DE' => ['eu-2'],
            'US' => ['eu-1', 'us-1'],
        ];
        foreach ($countriesToTest as $countryCode => $sources) {
            foreach ($sources as $sourceCode) {
                $source = $this->sourceRepository->get($sourceCode);
                $source->setData(
                    InitCountriesSelectionExtensionAttributes::DEFAULT_FOR_COUNTRIES_KEY,
                    $countryCode
                );
                $this->sourceRepository->save($source);
            }
        }

        $inventoryRequest = [
            'stockId' => 10,
            'items' => [
                [
                    'sku' => 'SKU-1',
                    'qty' => 1,
                ],
            ],
            'extension_attributes' => [
                'destination_address' => [
                    'country' => 'DE',
                    'postcode' => '45000',
                    'street' => 'test street',
                    'region' => 'Region',
                    'city' => 'City',
                ],
            ],
        ];

        $expectedResultData =
            [
                'DE' => [
                    'source_selection_items' => [
                        [
                            'source_code' => 'eu-2',
                            'sku' => 'SKU-1',
                            'qty_to_deduct' => 1,
                            'qty_available' => 3,
                        ]
                    ],
                    'shippable' => 1,
                ],
                'US' => [
                    'source_selection_items' => [
                        [
                            'source_code' => 'eu-1',
                            'sku' => 'SKU-1',
                            'qty_to_deduct' => 1,
                            'qty_available' => 5.5,
                        ],
                        [
                            'source_code' => 'eu-2',
                            'sku' => 'SKU-1',
                            'qty_to_deduct' => 1,
                            'qty_available' => 3,
                        ],
                    ],
                    'shippable' => 1,
                ],
            ];

        $algorithmCode = DefaultForCountryAlgorithm::CODE;
        $requestData = [
            'inventoryRequest' => $inventoryRequest,
            'algorithmCode' => $algorithmCode,
        ];

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Execute',
            ],
        ];

        foreach ($countriesToTest as $countryCode => $sources) {
            $requestData['inventoryRequest']['extension_attributes']['destination_address']['country'] =
                $countryCode;
            $sourceSelectionAlgorithmResult = (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
                ? $this->_webApiCall($serviceInfo, $requestData)
                : $this->_webApiCall($serviceInfo, $requestData);

            $this->assertIsArray($sourceSelectionAlgorithmResult);
            $this->assertNotEmpty($sourceSelectionAlgorithmResult);
            AssertArrayContains::assert($expectedResultData, $sourceSelectionAlgorithmResult);
        }
    }

    /**
     * Setup test instance
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultAlgorithmCode = Bootstrap::getObjectManager()->get(
            GetDefaultSourceSelectionAlgorithmCodeInterface::class
        );
        $this->sourceRepository = Bootstrap::getObjectManager()->get(
            SourceRepositoryInterface::class
        );
        $this->sourceFactory = Bootstrap::getObjectManager()->get(
            SourceInterfaceFactory::class
        );
    }
}
