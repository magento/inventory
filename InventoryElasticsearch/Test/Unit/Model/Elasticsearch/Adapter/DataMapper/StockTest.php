<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Model\Elasticsearch\Adapter\DataMapper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryElasticsearch\Model\Elasticsearch\Adapter\DataMapper\Stock as StockDataMapper;
use Magento\InventoryElasticsearch\Model\ResourceModel\Inventory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for additional field data mapper
 */
class StockTest extends TestCase
{
    /**
     * @var StockDataMapper
     */
    private $model;

    /**
     * @var Inventory|MockObject
     */
    private $inventoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->inventoryMock = $this->createMock(Inventory::class);
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $this->model = (new ObjectManager($this))->getObject(
            StockDataMapper::class,
            [
                'inventory' => $this->inventoryMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * Test for `testMap` when additional product data mapper attribute added
     *
     * @throws NoSuchEntityException
     */
    public function testMap(): void
    {
        $entityId = 1;
        $storeId = 1;
        $sku = '24-MB01';
        $websiteCode = 'base';

        $this->inventoryMock->expects($this->once())
            ->method('getSkuRelation')
            ->with($entityId)
            ->willReturn($sku);

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode'])
            ->getMock();

        $websiteMock->expects($this->once())->method('getCode')->willReturn($websiteCode);

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsite'])
            ->getMock();

        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->inventoryMock->expects($this->once())
            ->method('getStockStatus')
            ->with($sku, $websiteCode)
            ->willReturn(1);

        $this->assertSame(['is_out_of_stock' => 1], $this->model->map($entityId, $storeId));
    }
}
