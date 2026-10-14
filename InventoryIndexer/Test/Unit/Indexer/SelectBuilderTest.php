<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\GetIsStockItemSalableConditionInterface;
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
        $this->connection->method('getCheckSql')->willReturn('quantity_expression');
        $this->connection->method('fetchCol')->willReturn(['default']);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $salableCondition = $this->createMock(GetIsStockItemSalableConditionInterface::class);
        $salableCondition->method('execute')->willReturn('is_salable_expression');

        $reservationsIndexTable = $this->createMock(ReservationsIndexTable::class);
        $reservationsIndexTable->method('getTableName')->willReturn('reservations_temp');

        $this->selectBuilder = new SelectBuilder(
            $resourceConnection,
            $salableCondition,
            'catalog_product_entity',
            $reservationsIndexTable
        );
    }

    public function testGetSelectOrdersBySkuAscending(): void
    {
        $sourceCodesSelect = $this->createSelfReturningSelect();
        $indexSelect = $this->createSelfReturningSelect();
        $this->connection->method('select')
            ->willReturnOnConsecutiveCalls($sourceCodesSelect, $indexSelect);

        $indexSelect->expects(self::once())
            ->method('order')
            ->with('source_item.sku ASC')
            ->willReturnSelf();

        $this->selectBuilder->getSelect(2, ['sku1', 'sku2']);
    }

    /**
     * @return Select|MockObject
     */
    private function createSelfReturningSelect()
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'joinLeft', 'joinInner', 'where', 'group', 'columns'] as $method) {
            $select->method($method)->willReturnSelf();
        }

        return $select;
    }
}
