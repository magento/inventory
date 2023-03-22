<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Test\Fixture\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Test\Fixture\Attribute;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Test\Fixture\Source;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test validation on add source to child product of configurable product.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableProductShouldBeInStockWhenChildProductInStockTest extends WebapiAbstract
{
    private const SOURCE_ITEM_SERVICE_NAME_SAVE = 'inventoryApiSourceItemsSaveV1';
    private const SOURCE_ITEM_SERVICE_NAME_DELETE = 'inventoryApiSourceItemsDeleteV1';
    private const SOURCE_ITEM_RESOURCE_PATH = '/V1/inventory/source-items';
    private const CONFIGURABLE_PRODUCT_SKU = 'configurable_in_stock';
    private const CONFIGURABLE_CHILD_PRODUCT_SKU1 = 'simple_10';
    private const CONFIGURABLE_CHILD_PRODUCT_SKU2 = 'simple_20';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $storeCodeBefore;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var array[]
     */
    private $sourceItems = [
        [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => self::CONFIGURABLE_CHILD_PRODUCT_SKU1,
            SourceItemInterface::QUANTITY => 10,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => self::CONFIGURABLE_CHILD_PRODUCT_SKU2,
            SourceItemInterface::QUANTITY => 20,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => self::CONFIGURABLE_CHILD_PRODUCT_SKU1,
            SourceItemInterface::QUANTITY => 0,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'default',
            SourceItemInterface::SKU => self::CONFIGURABLE_CHILD_PRODUCT_SKU2,
            SourceItemInterface::QUANTITY => 0,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
        ],
    ];
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->stockRegistry = $this->objectManager->get(StockRegistryInterface::class);
        $this->getProductIdsBySkus = $this->objectManager->get(GetProductIdsBySkusInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->storeCodeBefore = $this->storeManager->getStore()->getCode();
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    protected function tearDown(): void
    {
        $this->deleteSourceItems($this->sourceItems);
        parent::tearDown();
    }

    /**
     * Test if configurable product out of stock if child product is out of stock
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     */
    public function testConfigurableProductIsInStockAfterSave()
    {
        $productSku = self::CONFIGURABLE_PRODUCT_SKU;
        $childSku1 = self::CONFIGURABLE_CHILD_PRODUCT_SKU1;
        $childSku2 = self::CONFIGURABLE_CHILD_PRODUCT_SKU2;
        $configurableProduct = $this->productRepository->get($productSku);
        self::assertEquals(SourceItemInterface::STATUS_IN_STOCK, (int) $configurableProduct->getStatus());
        $simpleChildProduct1 = $this->productRepository->get($childSku1);
        self::assertEquals(SourceItemInterface::STATUS_IN_STOCK, (int) $simpleChildProduct1->getStatus());
        $this->addSourceItems([$this->sourceItems[0]]);
        $actualData = $this->getSourceItems($childSku1);
        self::assertEquals(1, $actualData['total_count']);
        $this->addSourceItems([$this->sourceItems[1]]);
        $actualData = $this->getSourceItems($childSku2);
        self::assertEquals(1, $actualData['total_count']);
        $this->addSourceItems([$this->sourceItems[2]]);
        $actualData = $this->getSourceItems($childSku1);
        self::assertEquals(SourceItemInterface::STATUS_OUT_OF_STOCK, $actualData['items'][0]['status']);
        self::assertEquals(0, $actualData['items'][0]['quantity']);
    }

    /**
     * Test if configurable product back in stock if child product is in stock again
     *
     */
    #[
        AppArea('frontend'),
        DataFixture(Source::class, as: 'src'),
        DataFixture(Attribute::class, ['options' => [['label' => 'option', 'sort_order' => 0]]], as:'attribute'),
        DataFixture(Product::class, as: 'simple'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attribute$'], '_links' => ['$simple$']],
            as: 'configurable'
        )
    ]
    public function testConfigurableProductIsInStockOnDefaultSourceAfterSave()
    {
        $simpleProduct = $this->fixtures->get('simple');
        $configurableProduct = $this->fixtures->get('configurable');

        $collection = $this->getLayerProductCollection($configurableProduct->getSku());
        self::assertEquals(1, $collection->count());

        $this->addSourceItems([
            [
                SourceItemInterface::SOURCE_CODE => 'default',
                SourceItemInterface::SKU => $simpleProduct->getSku(),
                SourceItemInterface::QUANTITY => 0,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK
            ]
        ]);

        $collection = $this->getLayerProductCollection($configurableProduct->getSku());
        self::assertEquals(0, $collection->count());

        $this->addSourceItems([
            [
                SourceItemInterface::SOURCE_CODE => 'default',
                SourceItemInterface::SKU => $simpleProduct->getSku(),
                SourceItemInterface::QUANTITY => 100,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
            ]
        ]);

        $collection = $this->getLayerProductCollection($configurableProduct->getSku());
        self::assertEquals(1, $collection->count());
    }

    /**
     * Get layer product collection for frontend
     *
     * @param string $sku
     * @return Collection
     */
    private function getLayerProductCollection(string $sku): Collection
    {
        $collection = $this->objectManager->get(CollectionFactory::class)->create();
        $collection->addAttributeToFilter('sku', $sku)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents();
        return $collection;
    }

    /**
     * Add source items data for the configurable product
     *
     * @param array $sourceItems
     */
    private function addSourceItems(array $sourceItems): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::SOURCE_ITEM_RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SOURCE_ITEM_SERVICE_NAME_SAVE,
                'operation' => self::SOURCE_ITEM_SERVICE_NAME_SAVE . 'Execute',
            ],
        ];
        $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);
    }

    /**
     * Delete the source items
     *
     * @param array $sourceItems
     */
    private function deleteSourceItems(array $sourceItems): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::SOURCE_ITEM_RESOURCE_PATH . '-delete',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SOURCE_ITEM_SERVICE_NAME_DELETE,
                'operation' => self::SOURCE_ITEM_SERVICE_NAME_DELETE . 'Execute',
            ],
        ];
        $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);
    }

    /**
     * Get source item details by sku
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
                SearchCriteria::PAGE_SIZE => 10
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::SOURCE_ITEM_RESOURCE_PATH . '?' . http_build_query($requestData),
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
