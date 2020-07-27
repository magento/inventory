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
 * Test validation on modify bundle product selection with shipment types "Ship Together" and "Ship Separately".
 */
class BundleProductShipTogetherSaveChildValidationTest extends WebapiAbstract
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
     * Verify, simple product cannot be replaced in bundle product "Ship Together" in case of multiple sources.
     *
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryBundleProduct::Test/_files/product_bundle_ship_together.php
     */
    public function testAddOptionShipmentTypeTogetherMultipleSources(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be added to bundle product');
        $bundleProduct = $this->productRepository->get('bundle-ship-together');
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $option = current($options);
        $simple = $this->productRepository->get('SKU-1');
        $productLink = current($option->getProductLinks());
        $linkedProduct = [
            'id' => $productLink->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $this->saveChild($bundleProduct->getSku(), $linkedProduct);
    }

    /**
     * Verify, simple product can be replaced in bundle product "Ship Together" in case of single source.
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
        $productLink = current($option->getProductLinks());
        $linkedProduct = [
            'id' => $productLink->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $result = $this->saveChild($bundleProduct->getSku(), $linkedProduct);
        self::assertTrue($result);
    }

    /**
     * Verify, simple product can be replaced in bundle product "Ship Separately" in case of multiple sources.
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
        $productLink = current($option->getProductLinks());
        $linkedProduct = [
            'id' => $productLink->getId(),
            'sku' => $simple->getSku(),
            'option_id' => $option->getId(),
            'qty' => 1,
            'position' => 1,
            'priceType' => 2,
            'price' => 10,
            'is_default' => true,
            'can_change_quantity' => 0,
        ];
        $result = $this->saveChild($bundleProduct->getSku(), $linkedProduct);
        self::assertTrue($result);
    }

    /**
     * Make Web Api call to save bundle product selection.
     *
     * @param string $productSku
     * @param array $linkedProduct
     * @return bool
     */
    private function saveChild(string $productSku, array $linkedProduct): bool
    {
        $resourcePath = self::RESOURCE_PATH . '/:sku/links/:id';
        $serviceInfo = [
            'rest' => [
                'resourcePath' => str_replace(
                    [':sku', ':id'],
                    [$productSku, $linkedProduct['id']],
                    $resourcePath
                ),
                'httpMethod' => Request::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'SaveChild',
            ],
        ];
        return $this->_webApiCall(
            $serviceInfo,
            ['sku' => $productSku, 'linkedProduct' => $linkedProduct]
        );
    }
}
