<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\InventoryCatalog\Model\Cache\ProductTypesBySkusStorage;
use Magento\InventoryCatalog\Model\ResourceModel\GetProductTypesBySkus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests getting Product type by product SKU.
 */
class GetProductTypesBySkusTest extends TestCase
{
    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var ProductTypesBySkusStorage
     */
    private $productTypesBySkuCacheStorage;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getProductTypesBySkus = Bootstrap::getObjectManager()->get(GetProductTypesBySkusInterface::class);
        $this->productTypesBySkuCacheStorage = Bootstrap::getObjectManager()->get(ProductTypesBySkusStorage::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/products_all_types.php
     */
    public function testExecute()
    {
        $typesBySku = [
            'bundle_sku' => 'bundle',
            'configurable_sku' => 'configurable',
            'simple_sku' => 'simple',
            'downloadable_sku' => 'downloadable',
            'grouped_sku' => 'grouped',
            'virtual_sku' => 'virtual',
        ];

        self::assertEquals($typesBySku, $this->getProductTypesBySkus->execute(array_keys($typesBySku)));
    }

    /**
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/products_all_types.php
     */
    public function testExecuteWithNotExistedSkus()
    {
        $skus = ['not_existed_1', 'not_existed_2', 'simple_sku'];

        self::assertEquals(['simple_sku' => 'simple'], $this->getProductTypesBySkus->execute($skus));
    }

    /**
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/products_types_numeric_skus.php
     */
    public function testExecuteWithSimilairNumericSkus()
    {
        $skus = ['35.420', '35.4200', '35.42000'];

        $expectedOutput = [
            '35.420' => 'bundle',
            '35.4200' => 'configurable',
            '35.42000' => 'downloadable'
        ];

        // Clean cache so the product types come from GetProductTypesBySkus directly
        $this->productTypesBySkuCacheStorage->clean();

        self::assertEquals($expectedOutput, $this->getProductTypesBySkus->execute($skus));
    }
}
