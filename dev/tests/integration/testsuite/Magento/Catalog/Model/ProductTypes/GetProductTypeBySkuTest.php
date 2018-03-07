<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Model\ProductTypes;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Tests getting correct product type by product sku.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class GetProductTypeBySkuTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var GetProductTypeBySku
     */
    private $model;

    /**
     * Tests getting correct product type by product sku.
     *
     * @param string $expectedType
     * @magentoDataFixture Magento/Catalog/_files/products_all_types.php
     * @dataProvider productTypesDataProvider
     */
    public function testProductTypes(string $expectedType)
    {
        $actualType = $this->model->execute($expectedType . '_sku');
        $this->assertEquals(
            $expectedType,
            $actualType
        );
    }

    /**
     * Data provider for testProductTypes.
     *
     * @return array
     */
    public function productTypesDataProvider()
    {
        return [
            ['bundle'],
            ['configurable'],
            ['downloadable'],
            ['grouped'],
            ['simple'],
            ['virtual'],
        ];
    }

    protected function setUp()
    {
        $this->model = Bootstrap::getObjectManager()->get(GetProductTypeBySku::class);
    }
}
