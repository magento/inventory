<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Model\ResourceModel;

use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemsData;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemsDataCache;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GetStockItemsDataCacheTest extends TestCase
{
    /**
     * @var GetStockItemsData|MockObject
     */
    private $getStockItemsDataMock;

    /**
     * @var CacheStorage|MockObject
     */
    private $cacheStorageMock;

    /**
     * @var GetStockItemsDataCache
     */
    private GetStockItemsDataCache $getStockItemsDataCache;

    protected function setUp(): void
    {
        $this->getStockItemsDataMock = $this->createMock(GetStockItemsData::class);
        $this->cacheStorageMock = $this->createMock(CacheStorage::class);

        $this->getStockItemsDataCache = new GetStockItemsDataCache(
            $this->getStockItemsDataMock,
            $this->cacheStorageMock
        );
    }

    public function testExecute()
    {
        $skus = ['sku1', 'sku2'];
        $stockId = 123;
        $cachedData = ['cachedData'];
        $fetchedData = ['fetchedData'];

        // Setup cache storage to return cached data for sku1
        $this->cacheStorageMock
            ->method('get')
            ->willReturnCallback(function ($requestedStockId, $requestedSku) use ($stockId, $cachedData) {
                if ($requestedSku == 'sku1' && $requestedStockId == $stockId) {
                    return $cachedData;
                }
                return null;
            });

        // Setup GetStockItemsData to return fetched data for sku2
        $this->getStockItemsDataMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(['sku2']), $this->equalTo($stockId))
            ->willReturn(['sku2' => $fetchedData]);

        // Expect cache storage to set fetched data for sku2
        $this->cacheStorageMock
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo($stockId), $this->equalTo('sku2'), $this->equalTo($fetchedData));

        $result = $this->getStockItemsDataCache->execute($skus, $stockId);

        // Assertions
        $this->assertArrayHasKey('sku1', $result);
        $this->assertEquals($cachedData, $result['sku1']);

        $this->assertArrayHasKey('sku2', $result);
        $this->assertEquals($fetchedData, $result['sku2']);
    }
}
