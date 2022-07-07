<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryElasticsearch\Model\Elasticsearch\Adapter\DataMapper\Stock as StockDataMapper;
use Magento\InventoryElasticsearch\Model\ResourceModel\Inventory as StockInventory;
use Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper\ProductDataMapperPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for additional field product data mapper plugin
 */
class ProductDataMapperPluginTest extends TestCase
{
    /**
     * @var ProductDataMapperPlugin
     */
    private $plugin;

    /**
     * @var StockDataMapper|MockObject
     */
    private $stockDataMapperMock;

    /**
     * @var StockInventory|MockObject
     */
    private $stockInventoryMock;

    /**
     * @var ProductDataMapper|MockObject
     */
    private $productDataMapperMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->stockDataMapperMock = $this->createMock(StockDataMapper::class);
        $this->stockInventoryMock = $this->createMock(StockInventory::class);
        $this->productDataMapperMock = $this->createMock(ProductDataMapper::class);

        $this->plugin = (new ObjectManager($this))->getObject(
            ProductDataMapperPlugin::class,
            [
                'stockDataMapper' => $this->stockDataMapperMock,
                'inventory' => $this->stockInventoryMock
            ]
        );
    }

    /**
     * Test for `afterMap` when additional product data mapper attribute added
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testAfterMap(): void
    {
        $attribute = ['is_out_of_stock' => 1];
        $documents = [
            1 => [
                'store_id' => 1,
                'sku' => '24-MB01',
                'status' => 1
            ],
        ];
        $storeId = 1;
        $productId = 1;
        $expectedResult[1] = array_merge($documents[1], $attribute);

        $this->stockInventoryMock
            ->expects($this->once())
            ->method('saveRelation')
            ->with(array_keys($documents))
            ->willReturnSelf();
        $this->stockDataMapperMock
            ->expects($this->atLeastOnce())
            ->method('map')
            ->with($productId, $storeId)
            ->willReturn($attribute);
        $this->stockInventoryMock
            ->expects($this->once())
            ->method('clearRelation')
            ->willReturnSelf();

        $this->assertSame(
            $expectedResult,
            $this->plugin->afterMap($this->productDataMapperMock, $documents, [], $storeId, [])
        );
    }
}
