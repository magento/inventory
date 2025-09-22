<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStockApi\Test\Api;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\InventorySales\Model\ResourceModel\DeleteReservationsBySkus;
use Magento\InventorySales\Test\Api\OrderPlacementBase;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Salable qty export tests for different types of products.
 *
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/3042272
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/3042336
 */
class ExportStockSalableQtyTest extends OrderPlacementBase
{
    private const API_PATH = '/V1/inventory/export-stock-salable-qty';
    public const SERVICE_NAME = 'inventoryExportStockApiExportStockSalableQtyV1';

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * Verify salable qty export with reservations simple product types - default stock, default website.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable_with_files.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoApiDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider simpleProductTypesDataProvider()
     * @param array $filters
     * @return void
     */
    public function testExportSimpleProductTypesWithReservationsDefaultWebsiteDefaultStock(array $filters): void
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $simpleProductSKU = 'SKU-1';
        $virtualProductSKU = 'virtual-product';
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [['filters' => $filters]]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $virtualProductSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simpleProductSKU,
                    'qty' => 5.5,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addProduct($simpleProductSKU);
        $this->addProduct($virtualProductSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $virtualProductSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simpleProductSKU,
                    'qty' => 4.5,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify salable qty export with reservations simple product types - additional stock, additional website.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture Magento_InventoryCatalog::Test/_files/product_virtual_source_item_on_additional_source.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider simpleProductTypesDataProvider()
     * @param array $filters
     * @return void
     */
    public function testExportSimpleProductTypesWithReservationsAdditionalWebsiteAdditionalStock(array $filters): void
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_eu_website');
        $simpleSKU = 'SKU-1';
        $virtualSKU = 'virtual-product';
        $this->assignProductsToWebsite([$simpleSKU, $virtualSKU], 'eu_website');
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [['filters' => $filters]]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'eu_website',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $virtualSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simpleSKU,
                    'qty' => 8.5,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addProduct($simpleSKU);
        $this->addProduct($virtualSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $virtualSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simpleSKU,
                    'qty' => 7.5,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Export salable qty for simple product types data provider.
     *
     * @return array
     */
    public static function simpleProductTypesDataProvider()
    {
        return [
            [
                'filters' => [
                    [
                        'field' => SourceItemInterface::SKU,
                        'value' => 'downloadable-product',
                        'condition_type' => 'eq',
                    ],
                    [
                        'field' => SourceItemInterface::SKU,
                        'value' => 'virtual-product',
                        'condition_type' => 'eq',
                    ],
                    [
                        'field' => SourceItemInterface::SKU,
                        'value' => 'SKU-1',
                        'condition_type' => 'eq',
                    ],
                ],
            ]
        ];
    }

    /**
     * Verify salable qty export with reservations configurable product - default stock, default website.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     *
     * @return void
     */
    public function testExportConfigurableWithReservationsDefaultWebsiteDefaultStock(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $configurableSKU = 'configurable_in_stock';
        $firstOptionSKU = 'simple_10';
        $secondsOptionSKU = 'simple_20';
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $configurableSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $firstOptionSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $secondsOptionSKU,
                                'condition_type' => 'eq',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $configurableSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addConfigurableProduct($configurableSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $configurableSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify salable qty export with reservations configurable product - additional stock, additional website.
     *
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoApiDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testExportConfigurableWithReservationsAdditionalWebsiteAdditionalStock(): void
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_us_website');
        $configurableSKU = 'configurable';
        $firstOptionSKU = 'simple_10';
        $secondsOptionSKU = 'simple_20';
        $this->assignProductsToWebsite([$configurableSKU, $firstOptionSKU, $secondsOptionSKU], 'us_website');
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $configurableSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $firstOptionSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $secondsOptionSKU,
                                'condition_type' => 'eq',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'us_website',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $configurableSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addConfigurableProduct($configurableSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $configurableSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify salable qty export with reservations grouped product - default stock, default website.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryGroupedProduct::Test/_files/default_stock_grouped_products.php
     *
     * @return void
     */
    public function testExportGroupedWithReservationsDefaultWebsiteDefaultStock(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $groupedSKU = 'grouped_in_stock';
        $firstOptionSKU = 'simple_11';
        $secondsOptionSKU = 'simple_22';
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $groupedSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $firstOptionSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $secondsOptionSKU,
                                'condition_type' => 'eq',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $groupedSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addProduct($groupedSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $groupedSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify salable qty export with reservations grouped product - additional stock, additional website.
     *
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/custom_stock_with_eu_website_grouped_products.php
     * @magentoApiDataFixture Magento_InventoryGroupedProductIndexer::Test/_files/source_items_grouped_multiple.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @return void
     */
    public function testExportGroupedWithReservationsAdditionalWebsiteAdditionalStock(): void
    {
        $this->_markTestAsRestOnly();
        $this->setStoreView('store_for_us_website');
        $groupedSKU = 'grouped_in_stock';
        $firstOptionSKU = 'simple_11';
        $secondsOptionSKU = 'simple_22';
        $this->assignProductsToWebsite([$groupedSKU, $firstOptionSKU, $secondsOptionSKU], 'us_website');
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $groupedSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $firstOptionSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $secondsOptionSKU,
                                'condition_type' => 'eq',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'us_website',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $groupedSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 100,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addProduct($groupedSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $groupedSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $firstOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
                [
                    'sku' => $secondsOptionSKU,
                    'qty' => 99,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify salable qty export with reservations bundle product - default stock, default website.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento/Bundle/_files/product.php
     *
     * @return void
     */
    public function testExportBundleWithReservationsDefaultWebsiteDefaultStock(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignStockToWebsite(1, 'base');
        $bundleSKU = 'bundle-product';
        $simple = 'simple';
        $this->cleanUpReservations([$simple]);
        $requestData = [
            'searchCriteria' => [
                SearchCriteria::FILTER_GROUPS => [
                    [
                        'filters' => [
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $bundleSKU,
                                'condition_type' => 'eq',
                            ],
                            [
                                'field' => SourceItemInterface::SKU,
                                'value' => $simple,
                                'condition_type' => 'eq',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $bundleSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simple,
                    'qty' => 22,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->createCustomerCart();
        $this->addBundleProduct($bundleSKU);
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $result = $this->_webApiCall($serviceInfo);
        $this->verifyProducts(
            [
                [
                    'sku' => $bundleSKU,
                    'qty' => 0,
                    'is_salable' => true,
                ],
                [
                    'sku' => $simple,
                    'qty' => 20,
                    'is_salable' => true,
                ],
            ],
            $result['items']
        );
        $this->cancelOrder($orderId);
    }

    /**
     * Verify product export is correct.
     *
     * @param array $products
     * @param array $result
     * @return void
     */
    private function verifyProducts(array $products, array $result): void
    {
        $productsNum = count($products);
        $found = 0;
        foreach ($result as $resultProduct) {
            foreach ($products as $expectedProduct) {
                if ($resultProduct['sku'] === $expectedProduct['sku']) {
                    $found++;
                    self::assertEquals($expectedProduct, $resultProduct);
                }
            }
        }
        self::assertEquals($productsNum, $found);
    }

    /**
     * Clean up reservation for given product skus which may be created before test.
     *
     * @param array $skus
     * @return void
     */
    private function cleanUpReservations(array $skus): void
    {
        $deleteReservations = Bootstrap::getObjectManager()->get(DeleteReservationsBySkus::class);
        $deleteReservations->execute($skus);
    }

    /**
     * Test pagination total count with 4 products assigned to non-default stock, 1 to default stock.
     */
    #[DataFixture(SourceFixture::class, ['source_code' => 'test_source'], 'source')]
    #[DataFixture(StockFixture::class, as: 'stock')]
    #[DataFixture(
        StockSourceLinksFixture::class,
        [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
    )]
    #[DataFixture(
        StockSalesChannelsFixture::class,
        ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
    )]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-1'], 'p1')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-2'], 'p2')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-3'], 'p3')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-4'], 'p4')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-5'], 'p5')]
    #[DataFixture(
        SourceItemsFixture::class,
        [
            ['sku' => '$p1.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p2.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p3.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p4.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p5.sku$', 'source_code' => 'default', 'quantity' => 10, 'status' => 1],
        ]
    )]
    #[DataFixture('Magento_InventoryIndexer::Test/_files/reindex_inventory.php')]
    public function testPaginationNonDefaultStock(): void
    {
        $this->_markTestAsRestOnly();

        // Get the stock ID from the fixture
        $stockId = $this->fixtures->get('stock')->getStockId();

        // Ensure the stock is linked to the website
        $this->assignStockToWebsite($stockId, 'base');

        // Test non-default stock with page_size = 2
        $requestData = [
            'searchCriteria' => [
                'page_size' => 2
            ]
        ];

        $result = $this->_webApiCall([
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ]);

        // Should return 4 products assigned to non-default stock
        self::assertEquals(4, $result['total_count'], 'Non-default stock should return 4 products');
        self::assertEquals(2, count($result['items']), 'Page size 2 should return 2 items');
    }

    /**
     * Test pagination total count with 1 product assigned to non-default stock, 4 to default stock.
     */
    #[DataFixture(SourceFixture::class, ['source_code' => 'test_source'], 'source')]
    #[DataFixture(StockFixture::class, as: 'stock')]
    #[DataFixture(
        StockSourceLinksFixture::class,
        [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
    )]
    #[DataFixture(
        StockSalesChannelsFixture::class,
        ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
    )]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-1'], 'p1')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-2'], 'p2')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-3'], 'p3')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-4'], 'p4')]
    #[DataFixture(ProductFixture::class, ['sku' => 'test-product-5'], 'p5')]
    #[DataFixture(
        SourceItemsFixture::class,
        [
            ['sku' => '$p1.sku$', 'source_code' => '$source.source_code$', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p2.sku$', 'source_code' => 'default', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p3.sku$', 'source_code' => 'default', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p4.sku$', 'source_code' => 'default', 'quantity' => 10, 'status' => 1],
            ['sku' => '$p5.sku$', 'source_code' => 'default', 'quantity' => 10, 'status' => 1],
        ]
    )]
    #[DataFixture('Magento_InventoryIndexer::Test/_files/reindex_inventory.php')]
    public function testPaginationDefaultStock(): void
    {
        $this->_markTestAsRestOnly();

        // Ensure default stock is linked to the website
        $this->assignStockToWebsite(1, 'base');

        // Unassign product 1 from default stock
        $this->unassignProductFromDefaultStock('test-product-1');

        // Test default stock with page_size = 2
        $requestData = [
            'searchCriteria' => [
                'page_size' => 2
            ]
        ];

        $result = $this->_webApiCall([
            'rest' => [
                'resourcePath' => sprintf(
                    "%s/%s/%s?%s",
                    self::API_PATH,
                    'website',
                    'base',
                    http_build_query($requestData)
                ),
                'httpMethod' => Request::HTTP_METHOD_GET
            ],
        ]);

        // Should return 4 products assigned to default stock
        self::assertEquals(4, $result['total_count'], 'Default stock should return 4 products');
        self::assertEquals(2, count($result['items']), 'Page size 2 should return 2 items');
    }

    /**
     * Unassign product from default stock
     *
     * @param string $sku
     * @throws ValidationException
     */
    private function unassignProductFromDefaultStock(string $sku): void
    {
        $bulkSourceUnassign = $this->objectManager->get(BulkSourceUnassignInterface::class);
        $bulkSourceUnassign->execute([$sku], ['default']);
    }
}
