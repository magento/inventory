<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UpdateLegacyStock
 */
class UpdateLegacyStockStatusTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resource;
    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private $getProductIdsBySkus;
    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;
    /**
     * @var UpdateLegacyStockStatus
     */
    private $model;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->method('getTableName')->willReturnArgument(0);
        $this->getProductIdsBySkus = $this->createMock(GetProductIdsBySkusInterface::class);
        $this->getProductIdsBySkus->method('execute')->willReturnCallback('array_flip');
        $this->model = new UpdateLegacyStockStatus(
            $this->resource,
            $this->getProductIdsBySkus
        );
    }

    /**
     * Test that stock status changes are saved in the database
     */
    public function testExecute(): void
    {
        $salability = ['P1' => false, 'P2' => true];
        $tableName = 'cataloginventory_stock_status';
        $this->connection->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($tableName) {
                if ($arg1 == $tableName &&
                    empty($arg2['stock_status']) &&
                    $arg3['product_id = ?'] == 0) {
                    return ['stock_status' => false];
                } elseif ($arg1 == $tableName &&
                    $arg2['stock_status'] == 1 &&
                    $arg3['product_id = ?'] == 1) {
                    return ['stock_status' => true];
                }
            });

        $this->model->execute($salability);
    }
}
