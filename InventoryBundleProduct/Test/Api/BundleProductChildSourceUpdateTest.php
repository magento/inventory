<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Test\Api;

use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test source and stock changes of child product.
 */
class BundleProductChildSourceUpdateTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'bundleProductLinkManagementV1';
    private const SERVICE_VERSION = 'V1';
    private const RESOURCE_PATH = '/V1/bundle-products';

    private const SOURCE_ITEM_RESOURCE_PATH = '/V1/inventory/source-items';
    private const SOURCE_ITEM_SERVICE_NAME_SAVE = 'inventoryApiSourceItemsSaveV1';
    private const SOURCE_ITEM_SERVICE_NAME_DELETE = 'inventoryApiSourceItemsDeleteV1';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array[]
     */
    private $sourceItems = [
        [
            SourceItemInterface::SOURCE_CODE => 'eu-1',
            SourceItemInterface::SKU => 'SKU-4',
            SourceItemInterface::QUANTITY => 10,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
        [
            SourceItemInterface::SOURCE_CODE => 'eu-2',
            SourceItemInterface::SKU => 'SKU-4',
            SourceItemInterface::QUANTITY => 20,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ],
    ];

    /**
     * @var GetLegacyStockItem
     */
    private $getLegacyStockItem;

    /**
     * @var CacheStorage
     */
    private $getLegacyStockItemCache;

    /**
     * @var \Magento\TestFramework\Fixture\DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->getLegacyStockItem = Bootstrap::getObjectManager()->get(GetLegacyStockItem::class);
        $this->getLegacyStockItemCache = Bootstrap::getObjectManager()->get(CacheStorage::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->deleteSourceItems($this->sourceItems);
        parent::tearDown();
    }

    /**
     * Verify, new source cannot be added to child product if the bundle product is "Ship Together"
     * and other child products are not assigned to the new source.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_together.php
     *
     */
    public function testAddSourceToChildShipmentTypeTogetherMultipleSources(): void
    {
        $this->_markTestAsRestOnly(
            'The exception message contains html tag which causes "SOAP-ERROR: Encoding: External reference"'
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not able to assign \"eu-1\" to product \"SKU-4\"');
        $bundleProduct = $this->productRepository->get('bundle-ship-together');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-4');
        $result = $this->addChild($bundleProduct, $option, $simple);
        self::assertNotNull($result);
        $this->addSourceItems($this->sourceItems);
    }

    /**
     * Verify, new source can be added to child product if the bundle product is "Ship Together"
     * and other child products are assigned to the new source.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_together.php
     */
    public function testAddSourceToChildShipmentTypeTogetherSingleSource(): void
    {
        $bundleProduct = $this->productRepository->get('bundle-ship-together');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-4');
        $result = $this->addChild($bundleProduct, $option, $simple);
        self::assertNotNull($result);
        $this->addSourceItems([$this->sourceItems[1]]);
        $actualData = $this->getSourceItems('SKU-4');
        self::assertEquals(1, $actualData['total_count']);
        AssertArrayContains::assert([$this->sourceItems[1]], $actualData['items']);
    }

    /**
     * Verify, new source can be added to child product if the bundle product is "Ship Separately"
     * regardless if other child products are not assigned to the new source.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_separately.php
     */
    public function testAddSourceToChildShipmentTypeSeparately(): void
    {
        $bundleProduct = $this->productRepository->get('bundle-ship-separately');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-4');
        $result = $this->addChild($bundleProduct, $option, $simple);
        self::assertNotNull($result);
        $this->addSourceItems($this->sourceItems);
        $actualData = $this->getSourceItems('SKU-4');
        self::assertEquals(count($this->sourceItems), $actualData['total_count']);
        self::assertCount(count($this->sourceItems), $actualData['items']);
        foreach ($this->sourceItems as $sourceItem) {
            self::assertContainsEquals($sourceItem, $actualData['items']);
        }
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p1$']], 'opt1'),
        DataFixture(
            BundleProductFixture::class,
            ['_options' => ['$opt1$']],
            'bundle1'
        ),
    ]
    public function testBundleProductStockStatusShouldBeUpdatedWhenChildProductStockStatusChange(): void
    {
        $bundleProductSku = $this->fixtures->get('bundle1')->getSku();
        $simpleProductSku = $this->fixtures->get('p1')->getSku();
        $this->assertTrue($this->getLegacyStockItem->execute($bundleProductSku)->getIsInStock());
        $sources = [
            [
                SourceItemInterface::SOURCE_CODE => 'default',
                SourceItemInterface::SKU => $simpleProductSku,
                SourceItemInterface::QUANTITY => 99,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
            ]
        ];
        $this->sourceItems = array_merge($this->sourceItems, $sources);
        $this->addSourceItems($sources);
        $this->getLegacyStockItemCache->delete($bundleProductSku);
        $this->assertFalse($this->getLegacyStockItem->execute($bundleProductSku)->getIsInStock());
        $sources = [
            [
                SourceItemInterface::SOURCE_CODE => 'default',
                SourceItemInterface::SKU => $simpleProductSku,
                SourceItemInterface::QUANTITY => 99,
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
            ]
        ];
        $this->addSourceItems($sources);
        $this->getLegacyStockItemCache->delete($bundleProductSku);
        $this->assertTrue($this->getLegacyStockItem->execute($bundleProductSku)->getIsInStock());
    }

    /**
     * @param $bundleProduct
     * @param $option
     * @param $childProduct
     * @return int
     */
    private function addChild($bundleProduct, $option, $childProduct): int
    {
        $linkedProduct = [
            'id' => $childProduct->getId(),
            'sku' => $childProduct->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $productSku = $bundleProduct->getSku();
        $optionId = (int) $option->getId();
        $resourcePath = self::RESOURCE_PATH . '/:sku/links/:optionId';
        $serviceInfo = [
            'rest' => [
                'resourcePath' => str_replace(
                    [':sku', ':optionId'],
                    [$productSku, $optionId],
                    $resourcePath
                ),
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'AddChildByProductSku',
            ],
        ];
        return $this->_webApiCall(
            $serviceInfo,
            ['sku' => $productSku, 'optionId' => $optionId, 'linkedProduct' => $linkedProduct]
        );
    }

    /**
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
