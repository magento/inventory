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
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrderItemsDataForOrdersInNotFinalState;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOrderItemsDataForOrdersInNotFinalStateTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $resourceConnection;
    /**
     * @var GetOrderItemsDataForOrdersInNotFinalState
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
            GetOrderItemsDataForOrdersInNotFinalState::class,
            [
                'resourceConnection' => $this->resourceConnection
            ]
        );
    }

    /**
     * Test that proper connection names are used to retrieve orders items.
     */
    public function testExecute(): void
    {
        $salesConnectionName = 'sales';
        $defaultConnectionName = 'default';
        $orders = [
            [
                'entity_id' => 1,
                'status' => Order::STATE_COMPLETE,
                'increment_id' => 1,
                'store_id' => 3,
                'sku' => 'QYU321-Z',
                'qty_ordered' => 1,
            ]
        ];
        $stores = [
            [
                'store_id' => 3,
                'website_id' => 2,
            ]
        ];
        $expected = [
            [
                'entity_id' => 1,
                'status' => Order::STATE_COMPLETE,
                'increment_id' => 1,
                'store_id' => 3,
                'sku' => 'QYU321-Z',
                'qty_ordered' => 1,
                'website_id' => 2,
            ]
        ];
        $selectOrders = $this->createSelectMock();
        $selectStores = $this->createSelectMock();
        $salesConnection = $this->createConnectionMock($selectOrders);
        $salesConnection->method('fetchAll')
            ->willReturn($orders);
        $defaultConnection = $this->createConnectionMock($selectStores);
        $defaultConnection->method('fetchAll')
            ->willReturn($stores);
        $this->resourceConnection->method('getTableName')
            ->willReturnArgument(0);
        $this->resourceConnection->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturnCallback(function ($arg) use (
                $salesConnectionName,
                $defaultConnectionName,
                $salesConnection,
                $defaultConnection
) {
                if ($arg == $salesConnectionName) {
                    return $salesConnection;
                } elseif ($arg == $defaultConnectionName) {
                    return $defaultConnection;
                }
            });

        $this->assertEquals($expected, $this->model->execute(10, 1));
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
