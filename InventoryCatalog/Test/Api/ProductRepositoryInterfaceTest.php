<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Api\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test product management api with source items
 */
class ProductRepositoryInterfaceTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'catalogProductRepositoryV1';
    private const SERVICE_VERSION = 'V1';
    private const RESOURCE_PATH = '/V1/products';
    private const SOURCE_ITEMS_SERVICE_NAME = 'inventoryApiSourceItemRepositoryV1';
    private const SOURCE_ITEMS_SERVICE_VERSION = 'V1';
    private const SOURCE_ITEMS_RESOURCE_PATH = '/V1/inventory/source-items';

    /**
     * Test that product created without quantity should not be automatically assigned to default source
     */
    public function testCreateWithoutQty(): void
    {
        $data = $this->getProductSampleData();
        $this->saveProduct($data);
        $sourceItems = $this->getSourceItems($data[ProductInterface::SKU]);
        $this->assertEmpty($sourceItems);
        $this->deleteProduct($data[ProductInterface::SKU]);
    }

    /**
     * Test that product created with quantity should be automatically assigned to default source
     */
    public function testCreateWithQty(): void
    {
        $data = $this->getProductSampleData();
        $data[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = [
            'stock_item' => [
                StockItemInterface::QTY => 100,
            ]
        ];
        $this->saveProduct($data);
        $sourceItems = $this->getSourceItems($data[ProductInterface::SKU]);
        $this->assertCount(1, $sourceItems);
        $this->assertEquals('default', $sourceItems[0][SourceItemInterface::SOURCE_CODE]);
        $this->deleteProduct($data[ProductInterface::SKU]);
    }

    /**
     * Save Product
     *
     * @param $product
     * @param string|null $storeCode
     * @param string|null $token
     * @return mixed
     */
    private function saveProduct($product, $storeCode = null, ?string $token = null)
    {
        if (isset($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as &$attribute) {
                if ($attribute['attribute_code'] == 'category_ids'
                    && !is_array($attribute['value'])
                ) {
                    $attribute['value'] = [""];
                }
            }
        }
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        if ($token) {
            $serviceInfo['rest']['token'] = $serviceInfo['soap']['token'] = $token;
        }
        $requestData = ['product' => $product];

        return $this->_webApiCall($serviceInfo, $requestData, null, $storeCode);
    }

    /**
     * Delete Product
     *
     * @param string $sku
     * @return void
     */
    private function deleteProduct(string $sku): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sku,
                'httpMethod' => Request::HTTP_METHOD_DELETE,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'DeleteById',
            ],
        ];
        (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, ['sku' => $sku]);
    }

    /**
     * Get Product
     *
     * @param string $sku
     * @return array
     */
    private function getSourceItems(string $sku): array
    {
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $sku,
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
                SearchCriteria::SORT_ORDERS => [
                    [
                        'field' => SourceItemInterface::SOURCE_CODE,
                        'direction' => SortOrder::SORT_DESC,
                    ],
                ],
                SearchCriteria::CURRENT_PAGE => 1,
                SearchCriteria::PAGE_SIZE => 1000,
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::SOURCE_ITEMS_RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SOURCE_ITEMS_SERVICE_NAME,
                'serviceVersion' => self::SOURCE_ITEMS_SERVICE_VERSION,
                'operation' => self::SOURCE_ITEMS_SERVICE_NAME . 'Save',
            ],
        ];
        $response = (TESTS_WEB_API_ADAPTER === self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, $requestData);

        return $response['items'];
    }

    /**
     * Get necessary sample data to create a product
     *
     * @return array
     */
    private function getProductSampleData(): array
    {
        $name = uniqid('simple-product-');
        return [
            ProductInterface::SKU => $name,
            ProductInterface::NAME => $name,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::ATTRIBUTE_SET_ID => 4,
        ];
    }
}
