<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test validation on add bundle product selection with shipment types "Ship Together" and "Ship Separately".
 */
class BundleProductShipTogetherAddChildValidationTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'bundleProductLinkManagementV1';
    private const SERVICE_VERSION = 'V1';
    private const RESOURCE_PATH = '/V1/bundle-products';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
    }

    /**
     * Verify, simple product cannot be added to bundle product "Ship Together" in case of multiple sources.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_together.php
     *
     */
    public function testAddOptionShipmentTypeTogetherMultipleSources(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be added to bundle product');
        $bundleProduct = $this->productRepository->get('bundle-ship-together');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-1');
        $linkedProduct = [
            'id' => $simple->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $this->addChild($bundleProduct->getSku(), (int)$option->getId(), $linkedProduct);
    }

    /**
     * Verify, simple product can be added to bundle product "Ship Together" in case of single source.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_together.php
     */
    public function testAddOptionShipmentTypeTogetherSingleSource(): void
    {
        $bundleProduct = $this->productRepository->get('bundle-ship-together');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-4');
        $linkedProduct = [
            'id' => $simple->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $result = $this->addChild($bundleProduct->getSku(), (int)$option->getId(), $linkedProduct);
        self::assertNotNull($result);
    }

    /**
     * Verify, simple product can be added to bundle product "Ship Separately" in case of multiple sources.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_separately.php
     */
    public function testAddOptionShipmentTypeSeparately(): void
    {
        $bundleProduct = $this->productRepository->get('bundle-ship-separately');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-1');
        $linkedProduct = [
            'id' => $simple->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $result = $this->addChild($bundleProduct->getSku(), (int)$option->getId(), $linkedProduct);
        self::assertNotNull($result);
    }

    /**
     * Make Api call for adding child to bundle product.
     *
     * @param string $productSku
     * @param int $optionId
     * @param array $linkedProduct
     * @return int
     */
    private function addChild(string $productSku, int $optionId, array $linkedProduct): int
    {
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
}
