<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\UpdateLegacyStock;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UpdateLegacyStock
 */
class UpdateLegacyStockTest extends TestCase
{
    /**
     * @var GetSalabilityDataForUpdate|MockObject
     */
    private $getSalabilityDataForUpdate;
    /**
     * @var UpdateLegacyStockStatus|MockObject
     */
    private $updateLegacyStockStatus;
    /**
     * @var UpdateLegacyStock
     */
    private $model;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getSalabilityDataForUpdate = $this->createMock(GetSalabilityDataForUpdate::class);
        $this->updateLegacyStockStatus = $this->createMock(UpdateLegacyStockStatus::class);
        $this->model = new UpdateLegacyStock(
            $this->getSalabilityDataForUpdate,
            $this->updateLegacyStockStatus
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
        $this->updateLegacyStockStatus->expects($this->once())
            ->method('execute')
            ->with($changes);
        $this->assertEquals($changes, $this->model->execute($reservation));
    }
}
