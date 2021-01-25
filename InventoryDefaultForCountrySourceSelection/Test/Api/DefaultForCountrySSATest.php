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
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test default_for_countries algorithm
 */
class DefaultForCountrySSATest extends WebapiAbstract
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
     * Test default for countries SSA with disabled exclude unmatched
     *
     * @magentoConfigFixture cataloginventory/source_selection_default_for_country/additional_algorithm priority
     * @magentoConfigFixture cataloginventory/source_selection_default_for_country/exclude_unmatched 0
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     */
    public function testDefaultForCountrySSA()
    {
        $countriesToTest = $this->getCountriesToTestData();
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

        $expectedListOrder = [
            'DE' => ['eu-2', 'eu-1'],
            'FR' => ['eu-1', 'eu-2'],
        ];
        $wrongListOrder = [
            'DE' => ['eu-1', 'eu-2'],
            'FR' => ['eu-2', 'eu-1'],
        ];
        $this->assertSourceSelectionOrderLists($expectedListOrder, $wrongListOrder);
    }

    /**
     * Test default for countries SSA with enabled exclude_unmatched config
     *
     * @magentoConfigFixture cataloginventory/source_selection_default_for_country/additional_algorithm priority
     * @magentoConfigFixture cataloginventory/source_selection_default_for_country/exclude_unmatched 1
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     */
    public function testDefaultForCountrySSAWithExcludeUnmatched()
    {
        $countriesToTest = $this->getCountriesToTestData();
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

        $expectedListOrder = [
            'DE' => ['eu-2'],
            'FR' => ['eu-1'],
        ];
        $wrongListOrder = [
            'DE' => ['eu-1', 'eu-2'],
            'FR' => ['eu-2', 'eu-1'],
        ];
        $this->assertSourceSelectionOrderLists($expectedListOrder, $wrongListOrder);
    }

    /**
     * @inheridoc
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

    /**
     * Call webapi service and assert order list
     *
     * @param array $expectedListOrder
     * @param array $wrongListOrder
     * @return void
     */
    private function assertSourceSelectionOrderLists(array $expectedListOrder, array $wrongListOrder): void
    {
        $countriesToTest = $this->getCountriesToTestData();
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
            $response = $this->_webApiCall($serviceInfo, $requestData);

            $this->assertIsArray($response);
            $this->assertNotEmpty($response);
            $this->assertArrayHasKey('source_selection_items', $response);
            $listByPriority = [];
            foreach ($response['source_selection_items'] as $sourceSelectionItem) {
                $this->assertIsArray($sourceSelectionItem);
                $this->assertArrayHasKey('source_code', $sourceSelectionItem);
                $listByPriority[] = $sourceSelectionItem['source_code'];
            }
            $this->assertEquals($expectedListOrder[$countryCode], $listByPriority);
            $this->assertNotEquals($wrongListOrder[$countryCode], $listByPriority);
        }
    }

    /**
     * Countries to test data array
     *
     * @return array
     */
    private function getCountriesToTestData(): array
    {
        return [
            'DE' => ['eu-2'],
            'FR' => ['eu-1'],
        ];
    }
}
