<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Test\Unit\Indexer;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SelectBuilderTest extends TestCase
{
    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var SelectBuilder
     */
    private $selectBuilder;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $indexNameBuilder = $this->createMock(IndexNameBuilder::class);
        $indexNameBuilder->method('setIndexId')->willReturnSelf();
        $indexNameBuilder->method('addDimension')->willReturnSelf();
        $indexNameBuilder->method('setAlias')->willReturnSelf();
        $indexNameBuilder->method('build')->willReturn($this->createMock(IndexName::class));

        $indexNameResolver = $this->createMock(IndexNameResolverInterface::class);
        $indexNameResolver->method('resolveName')->willReturn('inventory_stock_2');

        $metadata = $this->createMock(EntityMetadataInterface::class);
        $metadata->method('getLinkField')->willReturn('row_id');
        $metadataPool = $this->createMock(MetadataPool::class);
        $metadataPool->method('getMetadata')->willReturn($metadata);

        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->method('getId')->willReturn(1);

        $statusAttribute = $this->createMock(Attribute::class);
        $statusAttribute->method('getId')->willReturn(97);
        $eavConfig = $this->createMock(Config::class);
        $eavConfig->method('getAttribute')->willReturn($statusAttribute);

        $configuration = $this->createMock(InventoryConfigurationInterface::class);
        $configuration->method('getManageStock')->willReturn(1);

        $this->selectBuilder = new SelectBuilder(
            $resourceConnection,
            $indexNameBuilder,
            $indexNameResolver,
            $metadataPool,
            $defaultStockProvider,
            $eavConfig,
            $configuration
        );
    }

    public function testGetSelectOrdersBySkuAscending(): void
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'joinInner', 'joinLeft', 'where', 'group'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $this->connection->method('select')->willReturn($select);

        $select->expects(self::once())
            ->method('order')
            ->with('parent_product_entity.sku ASC')
            ->willReturnSelf();

        $this->selectBuilder->getSelect(2, ['configurable_1']);
    }

    public function testDisabledChildrenAreIgnoredWithoutDroppingTheParentRow(): void
    {
        $columns = [];
        $joinConditions = [];

        $select = $this->createMock(Select::class);
        foreach (['joinInner', 'where', 'group', 'order'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('from')
            ->willReturnCallback(function ($table, $cols) use ($select, &$columns) {
                $columns = $cols;
                return $select;
            });
        $select->method('joinLeft')
            ->willReturnCallback(function ($table, $condition) use ($select, &$joinConditions) {
                $joinConditions[array_key_first($table)] = $condition;
                return $select;
            });
        $this->connection->method('select')->willReturn($select);

        $this->selectBuilder->getSelect(2);

        self::assertStringContainsString(
            'MAX(IF(product_status.value = ' . ProductStatus::STATUS_ENABLED . ', stock.is_salable, 0))',
            $columns[IndexStructure::IS_SALABLE]
        );
        self::assertArrayHasKey('product_status', $joinConditions);
        self::assertStringContainsString(
            'product_status.store_id = ' . Store::DEFAULT_STORE_ID,
            $joinConditions['product_status']
        );
        self::assertStringNotContainsString('product_status.value =', $joinConditions['product_status']);
    }
}
