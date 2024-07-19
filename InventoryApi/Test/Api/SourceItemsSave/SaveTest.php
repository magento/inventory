<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Api\SourceItemsSave;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\TestCase\WebapiAbstract;

class SaveTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH = '/V1/inventory/source-items';
    const SERVICE_NAME_SAVE = 'inventoryApiSourceItemsSaveV1';
    const SERVICE_NAME_DELETE = 'inventoryApiSourceItemsDeleteV1';
    /**#@-*/

    private function createSourceItemsStub()
    {
        return [
            [
                SourceItemInterface::SOURCE_CODE => 'eu-1',
                SourceItemInterface::SKU => 'SKU-1',
                SourceItemInterface::QUANTITY => 5.5,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
            ],
            [
                SourceItemInterface::SOURCE_CODE => 'eu-2',
                SourceItemInterface::SKU => 'SKU-1',
                SourceItemInterface::QUANTITY => 3,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
            ],
        ];
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     */
    public function testSourceIntegrityFails()
    {
        $sourceItems = $this->createSourceItemsStub();
        foreach ($sourceItems as $item) {
            $item[SourceItemInterface::SOURCE_CODE] = strtoupper($item[SourceItemInterface::SOURCE_CODE]);
        }

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_SAVE,
                'operation' => self::SERVICE_NAME_SAVE . 'Execute',
            ],
        ];
        $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);

        $actualData = $this->getSourceItems();

        self::assertEquals(0, $actualData['total_count']);
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     *
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/529092/scenarios/1824126
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/530616/scenarios/1824143
     */
    public function testExecute()
    {
        $sourceItems = $this->createSourceItemsStub();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_SAVE,
                'operation' => self::SERVICE_NAME_SAVE . 'Execute',
            ],
        ];
        $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);

        $actualData = $this->getSourceItems();

        self::assertEquals(2, $actualData['total_count']);
        AssertArrayContains::assert($sourceItems, $actualData['items']);
    }

    protected function tearDown(): void
    {
        $sourceItems = $sourceItems = $this->createSourceItemsStub();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?'
                    . http_build_query(['sourceItems' => $sourceItems]),
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_DELETE,
                'operation' => self::SERVICE_NAME_DELETE . 'Execute',
            ],
        ];

        (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);
        parent::tearDown();
    }

    /**
     * @return array
     */
    private function getSourceItems(): array
    {
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => 'SKU-1',
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
                SearchCriteria::PAGE_SIZE => 10
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'inventoryApiSourceItemRepositoryV1',
                'operation' => 'inventoryApiSourceItemRepositoryV1GetList',
            ],
        ];
        return (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, $requestData);
    }
}
