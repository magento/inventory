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
