<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Test\Api\StockRepository;

use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class ValidationTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH = '/V1/inventory/stocks';
    const SERVICE_NAME = 'inventoryApiStockRepositoryV1';
    /**#@-*/

    /**
     * @param array $salesChannels
     * @param array $expectedErrorData
     *
     * @dataProvider          failedNonExistedWebsiteValidationDataProvider
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock.php
     */
    public function testNonExistedWebsiteValidationOnUpdate(array $salesChannels, array $expectedErrorData)
    {
        $stockId = 10;
        $stockName = 'stock-name-1';
        $data = [
            "stock_id" => $stockId,
            "name" => $stockName,
            "extension_attributes" => [
                "sales_channels" => $salesChannels
            ]
        ];

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $stockId,
                'httpMethod' => Request::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        $this->webApiCall($serviceInfo, $data, $expectedErrorData);
    }

    /**
     * @return array
     */
    public function failedNonExistedWebsiteValidationDataProvider(): array
    {
        return [
            'non_existed_'.SalesChannelInterface::TYPE_WEBSITE => [
                [
                    [
                        SalesChannelInterface::TYPE => SalesChannelInterface::TYPE_WEBSITE,
                        SalesChannelInterface::CODE => 'base'
                    ],
                    [
                        SalesChannelInterface::TYPE => SalesChannelInterface::TYPE_WEBSITE,
                        SalesChannelInterface::CODE => 'non_existed'
                    ],
                ],
                [
                    'message' => 'Validation Failed',
                    'errors' => [
                        [
                            'message' => 'Website with code "%code" does not exist. Cannot add sales channel.',
                            'parameters' => [
                                SalesChannelInterface::CODE => 'non_existed'
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @param array $serviceInfo
     * @param array $data
     * @param array $expectedErrorData
     *
     * @return void
     * @throws \Exception
     */
    private function webApiCall(array $serviceInfo, array $data, array $expectedErrorData)
    {
        try {
            $this->_webApiCall($serviceInfo, ['stock' => $data]);
            $this->fail('Expected throwing exception');
        } catch (\Exception $e) {
            if (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST) {
                self::assertEquals($expectedErrorData, $this->processRestExceptionResult($e));
                self::assertEquals(Exception::HTTP_BAD_REQUEST, $e->getCode());
            } elseif (TESTS_WEB_API_ADAPTER === self::ADAPTER_SOAP) {
                $this->assertInstanceOf('SoapFault', $e);
                $expectedWrappedErrors = [];
                foreach ($expectedErrorData['errors'] as $error) {
                    // @see \Magento\TestFramework\TestCase\WebapiAbstract::getActualWrappedErrors()
                    $expectedWrappedErrors[] = [
                        'message' => $error['message'],
                        'params' => $error['parameters'],
                    ];
                }
                $this->checkSoapFault($e, $expectedErrorData['message'], 'env:Sender', [], $expectedWrappedErrors);
            } else {
                throw $e;
            }
        }
    }
}
