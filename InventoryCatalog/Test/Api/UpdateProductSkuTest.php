<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Verify, source items will be updated after product sku has been updated.
 */
class UpdateProductSkuTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'catalogProductRepositoryV1';
    private const SERVICE_VERSION = 'V1';
    private const RESOURCE_PATH = '/V1/products';

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->getSourceItemsBySku = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
    }

    /**
     * Verify, update product sku will update product source items.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     *
     * @return void
     */
    public function testUpdateProductSku(): void
    {
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
        $product = $this->productRepository->get('SKU-1');
        $this->_webApiCall(
            $serviceInfo,
            ['product' => ['id' => $product->getId(), 'sku' => 'SKU-1_updated']]
        );

        $sourceItemsOldSku = $this->getSourceItemsBySku->execute('SKU-1');
        $sourceItemNewSku = $this->getSourceItemsBySku->execute('SKU-1_updated');
        self::assertEmpty($sourceItemsOldSku);
        self::assertNotEmpty($sourceItemNewSku);
    }

    /**
     * Revert product sku. {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $product = $this->productRepository->get('SKU-1_updated');
        $product->setSku('SKU-1');
        $this->productRepository->save($product);
    }
}
