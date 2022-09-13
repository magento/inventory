<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Test\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Framework\Webapi\Rest\Request;

/**
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/530616/scenarios/1824144
 */
class GetProductSalableQuantityTest extends WebapiAbstract
{
    private const API_PATH = '/V1/inventory/get-product-salable-quantity';
    private const SERVICE_NAME = 'inventorySalesApiGetProductSalableQtyV1';

    /**
     * Verify get product salable quantity will return correct quantity for given product and stock.
     *
     * @param string $sku
     * @param int $stockId
     * @param float $expectedResult
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @dataProvider getSalableQuantityDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testGetSalableQuantity(
        string $sku,
        int $stockId,
        float $expectedResult
    ) {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::API_PATH . '/' . $sku . '/' . $stockId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Execute',
            ],
        ];

        $res = (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, [
                'sku' => $sku,
                'stockId' => $stockId
            ]);

        self::assertEquals($expectedResult, $res);
    }

    /**
     * @return array
     */
    public function getSalableQuantityDataProvider(): array
    {
        return [
            ['SKU-1', 10, 8.5],
            ['SKU-1', 20, 0],
            ['SKU-2', 20, 5],
        ];
    }
}
