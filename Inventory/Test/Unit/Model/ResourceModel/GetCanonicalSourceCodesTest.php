<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Inventory\Model\ResourceModel\GetCanonicalSourceCodes;
use Magento\Inventory\Model\ResourceModel\Source;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCanonicalSourceCodesTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var GetCanonicalSourceCodes
     */
    private $getCanonicalSourceCodes;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->getCanonicalSourceCodes = new GetCanonicalSourceCodes($this->resourceConnection);
    }

    public function testExecuteMapsLowercasedCodeToStoredCode(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);

        $this->resourceConnection->method('getConnection')->willReturn($connection);
        $this->resourceConnection->method('getTableName')
            ->with(Source::TABLE_NAME_SOURCE)
            ->willReturn('inventory_source');

        $select->method('from')->with('inventory_source', ['source_code'])->willReturnSelf();
        $select->expects(self::once())
            ->method('where')
            ->with('source_code IN (?)', ['Warehouse_Bravo'])
            ->willReturnSelf();

        $connection->method('select')->willReturn($select);
        $connection->expects(self::once())
            ->method('fetchCol')
            ->with($select)
            ->willReturn(['warehouse_bravo']);

        $result = $this->getCanonicalSourceCodes->execute(['Warehouse_Bravo']);

        self::assertSame(['warehouse_bravo' => 'warehouse_bravo'], $result);
    }

    public function testExecuteQueriesOnlyUnresolvedCodes(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);

        $this->resourceConnection->expects(self::exactly(2))->method('getConnection')->willReturn($connection);
        $this->resourceConnection->method('getTableName')->willReturn('inventory_source');

        $expectedSourceCodes = [['Warehouse_Bravo'], ['eu-1']];
        $select->method('from')->willReturnSelf();
        $select->expects(self::exactly(2))
            ->method('where')
            ->willReturnCallback(
                function (string $condition, array $sourceCodes) use ($select, &$expectedSourceCodes): Select {
                    self::assertSame('source_code IN (?)', $condition);
                    self::assertSame(array_shift($expectedSourceCodes), $sourceCodes);
                    return $select;
                }
            );

        $connection->method('select')->willReturn($select);
        $connection->expects(self::exactly(2))
            ->method('fetchCol')
            ->willReturnOnConsecutiveCalls(['warehouse_bravo'], ['EU-1']);

        self::assertSame(
            ['warehouse_bravo' => 'warehouse_bravo'],
            $this->getCanonicalSourceCodes->execute(['Warehouse_Bravo'])
        );
        self::assertSame(
            ['warehouse_bravo' => 'warehouse_bravo', 'eu-1' => 'EU-1'],
            $this->getCanonicalSourceCodes->execute(['WAREHOUSE_BRAVO', 'eu-1'])
        );
    }

    public function testResetStateDropsResolvedCodes(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $select = $this->createStub(Select::class);

        $this->resourceConnection->expects(self::exactly(2))->method('getConnection')->willReturn($connection);
        $this->resourceConnection->method('getTableName')->willReturn('inventory_source');

        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $connection->method('select')->willReturn($select);
        $connection->expects(self::exactly(2))
            ->method('fetchCol')
            ->willReturn(['warehouse_bravo']);

        $this->getCanonicalSourceCodes->execute(['Warehouse_Bravo']);
        $this->getCanonicalSourceCodes->_resetState();
        $this->getCanonicalSourceCodes->execute(['Warehouse_Bravo']);
    }

    public function testExecuteReturnsEmptyArrayForEmptyInput(): void
    {
        $this->resourceConnection->expects(self::never())->method('getConnection');

        self::assertSame([], $this->getCanonicalSourceCodes->execute([]));
    }
}
