<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Unit\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryBundleProductIndexer\Indexer\OptionsStatusSelectBuilder;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        $this->connection->method('getCheckSql')->willReturn('check_expression');
        $this->connection->method('getIfNullSql')->willReturn('ifnull_expression');

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->method('getId')->willReturn(1);

        $optionsStatusSelectBuilder = $this->createMock(OptionsStatusSelectBuilder::class);
        $optionsStatusSelectBuilder->method('execute')->willReturn($this->createMock(Select::class));

        $configuration = $this->createMock(InventoryConfigurationInterface::class);
        $configuration->method('getManageStock')->willReturn(1);

        $this->selectBuilder = new SelectBuilder(
            $resourceConnection,
            $defaultStockProvider,
            $optionsStatusSelectBuilder,
            $configuration
        );
    }

    public function testGetSelectOrdersBySkuAscending(): void
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'joinLeft', 'where', 'group', 'columns'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $this->connection->method('select')->willReturn($select);

        $select->expects(self::once())
            ->method('order')
            ->with('product_entity.sku ASC')
            ->willReturnSelf();

        $this->selectBuilder->getSelect(2, ['bundle_1']);
    }
}
