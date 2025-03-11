<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Verify 'are-product-salable' WEB-API endpoint.
 */
class AreProductsSalableTest extends WebapiAbstract
{
    private const API_PATH = '/V1/inventory/are-products-salable';
    private const SERVICE_NAME = 'inventorySalesApiAreProductsSalableV1';
    private const SERVICE_VERSION = 'V1';

    /**
     * Verify product salable status for different stocks.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @dataProvider executeDataProvider
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $expectedResult
     * @return void
     */
    public function testProductSaleabilityOnDifferentStocks(
        string $sku,
        int $stockId,
        bool $expectedResult
    ): void {
        $request = [
            'skus' => [$sku],
            'stockId' => $stockId,
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::API_PATH . '?' . http_build_query($request),
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Execute',
            ],
        ];

        $res = $this->_webApiCall($serviceInfo, $request);
        $res = current($res);
        self::assertEquals($sku, $res['sku']);
        self::assertEquals($stockId, $res['stock_id']);
        self::assertEquals($expectedResult, $res['salable']);
    }

    /**
     * Provide test data.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            ['SKU-1', 10, true],
            ['SKU-1', 20, false],
            ['SKU-1', 30, true],
        ];
    }
}
