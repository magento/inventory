<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Unit\Model;

/**
 * Unit test for ProductIdLocator class.
 */
class ProductIdLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\LocatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $locatorService;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductIdLocator
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->locatorService = $this->getMockBuilder(\Magento\Catalog\Model\LocatorService::class)
            ->setMethods(['getProductLinkField', 'truncateToLimit', 'skuProcess'])
            ->disableOriginalConstructor()->getMock();

        $this->collectionFactory = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\Catalog\Model\ProductIdLocator::class,
            [
                'collectionFactory' => $this->collectionFactory,
                'locatorService' => $this->locatorService
            ]
        );
    }

    /**
     * Test retrieve
     */
    public function testRetrieveProductIdsBySkus()
    {
        $skus = ['sku_1', 'sku_2'];
        $collection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->setMethods(['getIterator', 'addFieldToFilter'])
            ->disableOriginalConstructor()->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->setMethods(['getSku', 'getData', 'getTypeId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->once())->method('addFieldToFilter')
            ->with(\Magento\Catalog\Api\Data\ProductInterface::SKU, ['in' => $skus])->willReturnSelf();
        $collection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $this->locatorService
            ->expects($this->once())
            ->method('getProductLinkField')
            ->with()
            ->willReturn('entity_id');

        $this->locatorService
            ->method('skuProcess')
            ->withConsecutive(['sku_1'], ['sku_1'], ['sku_2'])
            ->willReturnOnConsecutiveCalls('sku_1', 'sku_1', 'sku_2');

        $this->locatorService
            ->expects($this->once())
            ->method('truncateToLimit')
            ->with(['sku_1' => [1 => 'simple']])
            ->willReturn(['sku_1' => [1 => 'simple']]);
        $product->expects($this->once())->method('getSku')->willReturn('sku_1');
        $product->expects($this->once())->method('getData')->with('entity_id')->willReturn(1);
        $product->expects($this->once())->method('getTypeId')->willReturn('simple');
        $this->assertEquals(
            ['sku_1' => [1 => 'simple']],
            $this->model->retrieveProductIdsBySkus($skus)
        );
    }
}
