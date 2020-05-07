<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrdersTotalCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOrdersTotalCountTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $resourceConnection;
    /**
     * @var GetOrdersTotalCount
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->resourceConnection = $this->createMock(
            ResourceConnection::class
        );
        $this->model = $objectManager->getObject(
            GetOrdersTotalCount::class,
            [
                'resourceConnection' => $this->resourceConnection
            ]
        );
    }

    /**
     * Test that proper connections names are used to retrieve orders count.
     */
    public function testExecute(): void
    {
        $salesConnectionName = 'sales';
        $ordersCount = 423;
        $selectOrders = $this->createSelectMock();
        $salesConnection = $this->createConnectionMock($selectOrders);
        $salesConnection->method('fetchOne')
            ->willReturn($ordersCount);
        $this->resourceConnection->method('getTableName')
            ->willReturnArgument(0);
        $this->resourceConnection->expects($this->exactly(1))
            ->method('getConnection')
            ->with($salesConnectionName)
            ->willReturn($salesConnection);
        $this->assertEquals($ordersCount, $this->model->execute());
    }

    /**
     * @param MockObject $select
     * @return MockObject
     */
    private function createConnectionMock(MockObject $select): MockObject
    {
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->method('select')
            ->willReturn($select);
        return $connection;
    }

    /**
     * @return MockObject
     */
    private function createSelectMock(): MockObject
    {
        $select = $this->createPartialMock(
            Select::class,
            [
                'from',
                'where',
                'join',
            ]
        );
        $select->method('from')
            ->willReturnSelf();
        $select->method('where')
            ->willReturnSelf();
        $select->method('join')
            ->willReturnSelf();
        return $select;
    }
}
