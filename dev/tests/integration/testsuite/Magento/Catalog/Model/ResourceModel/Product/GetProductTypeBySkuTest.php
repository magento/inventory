<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Tests getting Product type by product SKU.
 */
class GetProductTypeBySkuTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetProductTypeBySku
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->model = Bootstrap::getObjectManager()->create(
            GetProductTypeBySku::class
        );
    }

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

    /**
     * Tests negative scenario when Product Sku is empty.
     *
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage Input data is empty
     */
    public function testExecuteEmpty()
    {
        $this->model->execute('');
    }
}
