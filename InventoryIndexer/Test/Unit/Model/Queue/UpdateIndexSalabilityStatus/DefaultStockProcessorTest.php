<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\DefaultStockProcessor;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateDefaultStockStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for DefaultStockProcessor
 */
class DefaultStockProcessorTest extends TestCase
{
    /**
     * @var GetSalabilityDataForUpdate|MockObject
     */
    private $getSalabilityDataForUpdate;
    /**
     * @var UpdateDefaultStockStatus|MockObject
     */
    private $updateDefaultStockStatus;
    /**
     * @var DefaultStockProcessor
     */
    private $model;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getSalabilityDataForUpdate = $this->createMock(GetSalabilityDataForUpdate::class);
        $this->updateDefaultStockStatus = $this->createMock(UpdateDefaultStockStatus::class);
        $this->model = new DefaultStockProcessor(
            $this->getSalabilityDataForUpdate,
            $this->updateDefaultStockStatus
        );
    }

    /**
     * Test that stock status changes are saved in the database
     */
    public function testExecute(): void
    {
        $skus = ['P1', 'P2'];
        $stockId = 1;
        $reservation = new ReservationData($skus, $stockId);
        $changes = ['P1' => false];
        $this->getSalabilityDataForUpdate->expects($this->once())
            ->method('execute')
            ->willReturn($changes);
        $this->updateDefaultStockStatus->expects($this->once())
            ->method('execute')
            ->with($changes, ResourceConnection::DEFAULT_CONNECTION);
        $this->assertEquals($changes, $this->model->execute($reservation));
    }
}
