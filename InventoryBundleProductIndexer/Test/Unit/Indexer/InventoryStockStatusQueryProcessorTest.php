<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Unit\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryBundleProductIndexer\Indexer\InventoryStockStatusQueryProcessor;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryStockStatusQueryProcessorTest extends TestCase
{
    /** @var ResourceConnection|MockObject */
    private ResourceConnection $resource;

    /** @var AdapterInterface|MockObject */
    private AdapterInterface $connection;

    /** @var StockIndexTableNameResolverInterface|MockObject */
    private StockIndexTableNameResolverInterface $stockTableResolver;

    /** @var Config|MockObject */
    private Config $eavConfig;

    /** @var MetadataPool|MockObject */
    private MetadataPool $metadataPool;

    /** @var EntityMetadataInterface|MockObject */
    private EntityMetadataInterface $metadata;

    /** @var Select|MockObject */
    private Select $select;

    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var InventoryStockStatusQueryProcessor
     */
    private InventoryStockStatusQueryProcessor $processor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->stockTableResolver = $this->createMock(StockIndexTableNameResolverInterface::class);
        $this->eavConfig = $this->createMock(Config::class);
        $this->metadataPool = $this->createMock(MetadataPool::class);
        $this->metadata = $this->createMock(EntityMetadataInterface::class);
        $this->defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $this->defaultStockProvider->expects($this->any())->method('getId')->willReturn(1);

        $this->select = $this->createMock(Select::class);

        $this->processor = new InventoryStockStatusQueryProcessor(
            $this->resource,
            $this->stockTableResolver,
            $this->eavConfig,
            $this->metadataPool,
            $this->defaultStockProvider
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function testExecuteInStockAndResolvesNonDefaultMsiStockOnly(): void
    {
        $this->resource->expects($this->exactly(3))
            ->method('getConnection')
            ->willReturn($this->connection);

        $attribute = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $attribute->method('getId')->willReturn(97);

        $this->eavConfig->expects($this->once())
            ->method('getAttribute')
            ->with(Product::ENTITY, ProductInterface::STATUS)
            ->willReturn($attribute);

        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->resource->method('getTableName')
            ->willReturnCallback(function (string $table): string {
                return $table;
            });

        $this->connection->method('select')
            ->willReturnCallback(function (): Select {
                $s = $this->createMock(Select::class);
                $s->method('from')->willReturnSelf();
                $s->method('joinInner')->willReturnSelf();
                $s->method('joinLeft')->willReturnSelf();
                $s->method('join')->willReturnSelf();
                $s->method('where')->willReturnSelf();
                $s->method('columns')->willReturnSelf();
                $s->method('union')->willReturnSelf();
                $s->method('group')->willReturnSelf();
                return $s;
            });

        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['stock_id' => 1, 'website_id' => 0],
                ['stock_id' => 2, 'website_id' => 2],
            ]);

        $this->stockTableResolver->expects($this->once())
            ->method('execute')
            ->with(2)
            ->willReturn('inventory_stock_2');

        $this->connection->expects($this->once())
            ->method('describeTable')
            ->with('inventory_stock_2')
            ->willReturn([
                'sku' => ['COLUMN_NAME' => 'sku'],
                'is_salable' => ['COLUMN_NAME' => 'is_salable'],
                'quantity' => ['COLUMN_NAME' => 'quantity'],
            ]);

        $this->select->expects($this->once())
            ->method('joinInner')
            ->with(
                $this->callback(function (array $from): bool {
                    // Expect ['stock' => <Select>]
                    return isset($from['stock']) && $from['stock'] instanceof Select;
                }),
                'stock.product_id = bs.product_id',
                []
            )
            ->willReturnSelf();

        $this->select->expects($this->once())
            ->method('where')
            ->with('stock_status = ?', Stock::STOCK_IN_STOCK)
            ->willReturnSelf();

        $result = $this->processor->execute($this->select);

        $this->assertSame($this->select, $result);
    }

    /**
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function testExecuteStillWorksWhenNoInventoryPartsAreGenerated(): void
    {
        $this->resource->expects($this->exactly(3))
            ->method('getConnection')
            ->willReturn($this->connection);

        $attribute = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $attribute->method('getId')->willReturn(97);

        $this->eavConfig->expects($this->once())
            ->method('getAttribute')
            ->with(Product::ENTITY, ProductInterface::STATUS)
            ->willReturn($attribute);

        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->resource->method('getTableName')->willReturnCallback(fn (string $t) => $t);

        $this->connection->method('select')
            ->willReturnCallback(function (): Select {
                $s = $this->createMock(Select::class);
                $s->method('from')->willReturnSelf();
                $s->method('joinInner')->willReturnSelf();
                $s->method('joinLeft')->willReturnSelf();
                $s->method('join')->willReturnSelf();
                $s->method('where')->willReturnSelf();
                $s->method('columns')->willReturnSelf();
                $s->method('union')->willReturnSelf();
                $s->method('group')->willReturnSelf();
                return $s;
            });
        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['stock_id' => 1, 'website_id' => 0],
            ]);

        $this->stockTableResolver->expects($this->never())->method('execute');
        $this->connection->expects($this->never())->method('describeTable');

        $this->select->expects($this->once())
            ->method('joinInner')
            ->with(
                $this->callback(function (array $from): bool {
                    return isset($from['stock']) && $from['stock'] instanceof Select;
                }),
                'stock.product_id = bs.product_id',
                []
            )
            ->willReturnSelf();

        $this->select->expects($this->once())
            ->method('where')
            ->with('stock_status = ?', Stock::STOCK_IN_STOCK)
            ->willReturnSelf();

        $result = $this->processor->execute($this->select);

        $this->assertSame($this->select, $result);
    }
}
