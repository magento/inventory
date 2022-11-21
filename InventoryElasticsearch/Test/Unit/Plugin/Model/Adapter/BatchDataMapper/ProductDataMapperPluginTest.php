<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper\ProductDataMapperPlugin;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;
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
     * @var GetSkusByProductIdsInterface|MockObject
     */
    private $getSkusByProductIdsMock;

    /**
     * @var StockIndexTableNameResolverInterface|MockObject
     */
    private $stockIndexTableNameResolverMock;

    /**
     * @var StockByWebsiteIdResolverInterface|MockObject
     */
    private $stockByWebsiteIdResolverMock;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    private $storeRepositoryMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var ProductDataMapper|MockObject
     */
    private $productDataMapperMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->getSkusByProductIdsMock = $this->createMock(GetSkusByProductIdsInterface::class);
        $this->stockIndexTableNameResolverMock = $this->createMock(StockIndexTableNameResolverInterface::class);
        $this->stockByWebsiteIdResolverMock = $this->getMockForAbstractClass(StockByWebsiteIdResolverInterface::class);
        $this->storeRepositoryMock = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->addMethods(['getWebsiteId'])
            ->getMockForAbstractClass();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getTableName'])
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->onlyMethods(['select', 'isTableExists', 'fetchPairs'])
            ->getMockForAbstractClass();
        $this->productDataMapperMock = $this->createMock(ProductDataMapper::class);
        $this->plugin = (new ObjectManager($this))->getObject(
            ProductDataMapperPlugin::class,
            [
                'getSkusByProductIds' => $this->getSkusByProductIdsMock,
                'stockIndexTableNameResolver' => $this->stockIndexTableNameResolverMock,
                'stockByWebsiteIdResolver' => $this->stockByWebsiteIdResolverMock,
                'storeRepository' => $this->storeRepositoryMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    /**
     * Test for `afterMap` when additional product data mapper attribute added
     *
     * @dataProvider stockDataProvider
     * @param int $storeId
     * @param int $websiteId
     * @param int $stockId
     * @param int $salability
     * @return void
     * @throws NoSuchEntityException
     */
    public function testAfterMap(int $storeId, int $websiteId, int $stockId, int $salability): void
    {
        $stockTable = 'inventory_stock_' . $stockId;
        $sku = '24-MB01';
        $attribute = ['is_out_of_stock' => $salability];
        $documents = [
            1 => [
                'store_id' => $storeId,
                'sku' => $sku,
                'status' => $salability
            ],
        ];
        $expectedResult[1] = array_merge($documents[1], $attribute);

        $this->storeRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($storeId)
            ->willReturnSelf();
        $this->storeRepositoryMock
            ->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $stock = $this->getMockForAbstractClass(StockInterface::class);
        $stock->method('getStockId')
            ->willReturn($stockId);
        $this->stockByWebsiteIdResolverMock
            ->method('execute')
            ->willReturn($stock);

        $this->stockIndexTableNameResolverMock
            ->expects($this->once())
            ->method('execute')
            ->with($stockId)
            ->willReturn($stockTable);

        $this->getSkusByProductIdsMock
            ->expects($this->once())
            ->method('execute')
            ->with(array_keys($documents))
            ->willReturn([$sku]);

        $this->resourceConnectionMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->with(ResourceConnection::DEFAULT_CONNECTION)
            ->willReturn($this->connectionMock);
        $this->resourceConnectionMock->expects($this->atLeastOnce())
            ->method('getTableName')
            ->willReturn($stockTable);

        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where'])
            ->getMock();
        $select->expects($this->once())
            ->method('from')
            ->with([$stockTable], ['sku', 'is_salable'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('where')
            ->with('sku IN (?)', [$sku])
            ->willReturnSelf();

        $this->connectionMock
            ->expects($this->once())
            ->method('isTableExists')
            ->with($stockTable)
            ->willReturn(true);
        $this->connectionMock->expects($this->atLeastOnce())
            ->method('select')
            ->willReturn($select);

        $this->connectionMock->expects($this->atLeastOnce())
            ->method('fetchPairs')
            ->willReturn([$sku => $salability]);

        $this->assertSame(
            $expectedResult,
            $this->plugin->afterMap($this->productDataMapperMock, $documents, [], $storeId)
        );
    }

    /**
     * @return array
     */
    public function stockDataProvider(): array
    {
        return [
            ['storeId' => 1, 'websiteId' => 1, 'stockId' => 1, 'saleability' => 1],
            ['storeId' => 2, 'websiteId' => 20, 'stockId' => 45, 'saleability' => 0],
        ];
    }
}
