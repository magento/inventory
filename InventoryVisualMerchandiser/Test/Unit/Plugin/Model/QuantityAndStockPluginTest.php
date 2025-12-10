<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryVisualMerchandiser\Test\Unit\Plugin\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DB\Ddl\Table;
use Magento\InventoryVisualMerchandiser\Plugin\Model\Resolver\QuantityAndStockPlugin;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuantityAndStockPluginTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection $resource;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var StockResolverInterface|MockObject
     */
    private StockResolverInterface $stockResolver;

    /**
     * @var StockIndexTableNameResolverInterface|MockObject
     */
    private StockIndexTableNameResolverInterface $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var MetadataPool|MockObject
     */
    private MetadataPool $metadataPool;

    /**
     * @inhertidoc
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(ResourceConnection::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->stockResolver = $this->createMock(StockResolverInterface::class);
        $this->stockIndexTableNameResolver = $this->createMock(StockIndexTableNameResolverInterface::class);
        $this->defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $this->metadataPool = $this->createMock(MetadataPool::class);

        parent::setUp();
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAroundJoinStockWebsiteAdminCode(): void
    {
        if (!class_exists(\Magento\VisualMerchandiser\Model\Resolver\QuantityAndStock::class)) {
            $this->markTestSkipped('VisualMerchandiser module is absent');
        }
        $websiteCode = 'admin';
        $subject = $this->createMock(\Magento\VisualMerchandiser\Model\Resolver\QuantityAndStock::class);
        $callable = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['callbackMethod'])
            ->getMock();
        $callable->method('callbackMethod')
            ->willReturn('test!');
        $callable->expects($this->never())->method('callbackMethod');
        $callback = [$callable, 'callbackMethod'];
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('getStoreId');
        $mainSelect = $this->createMock(Select::class);
        $mainSelect->expects($this->exactly(2))->method('joinLeft')->willReturnSelf();
        $mainSelect->expects($this->once())->method('columns')->willReturnSelf();
        $collection->expects($this->once())->method('getSelect')->willReturn($mainSelect);

        $parentStockTable = $this->createMock(Table::class);
        $parentStockTable->expects($this->exactly(2))->method('addColumn')->willReturnSelf();
        $parentStockTable->expects($this->once())->method('addIndex')->willReturnSelf();
        $parentStockTable->expects($this->once())->method('setOption')->willReturnSelf();
        $childRelationsTable = $this->createMock(Table::class);
        $childRelationsTable->expects($this->exactly(2))->method('addColumn')->willReturnSelf();
        $childRelationsTable->expects($this->exactly(2))->method('addIndex')->willReturnSelf();
        $childRelationsTable->expects($this->once())->method('setOption')->willReturnSelf();
        $childStockTable = $this->createMock(Table::class);
        $childStockTable->expects($this->exactly(2))->method('addColumn')->willReturnSelf();
        $childStockTable->expects($this->once())->method('addIndex')->willReturnSelf();
        $childStockTable->expects($this->once())->method('setOption')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->exactly(3))->method('newTable')->willReturnOnConsecutiveCalls(
            $parentStockTable,
            $childRelationsTable,
            $childStockTable
        );
        $connection->expects($this->exactly(3))->method('createTemporaryTable');
        $select = $this->createMock(Select::class);
        $select->expects($this->exactly(3))->method('from')->willReturnSelf();
        $select->expects($this->exactly(2))->method('group')->willReturnSelf();
        $select->expects($this->exactly(2))->method('join')->willReturnSelf();
        $connection->expects($this->exactly(3))->method('select')->willReturn($select);
        $connection->expects($this->exactly(3))->method('insertFromSelect');
        $connection->expects($this->exactly(3))->method('query');
        $this->resource->expects($this->exactly(3))->method('getConnection')->willReturn($connection);
        $this->resource->expects($this->exactly(7))->method('getTableName')->willReturnOnConsecutiveCalls(
            'parentStockTableName',
            'inventory_source_item',
            'childRelationsTableName',
            'catalog_product_relation',
            'catalog_product_entity',
            'childStockTableName',
            'inventory_source_item'
        );

        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->once())->method('getCode')->willReturn($websiteCode);
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($website);

        $plugin = new QuantityAndStockPlugin(
            $this->resource,
            $this->storeManager,
            $this->stockResolver,
            $this->stockIndexTableNameResolver,
            $this->defaultStockProvider,
            $this->metadataPool
        );
        $plugin->aroundJoinStock($subject, $callback, $collection);
    }
}
