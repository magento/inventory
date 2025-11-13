<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(UpdateIndexSalabilityStatus::class),
]
class UpdateIndexSalabilityStatusTest extends TestCase
{
    /**
     * @var IndexProcessor|MockObject
     */
    private $indexProcessor;

    /**
     * @var UpdateIndexSalabilityStatus
     */
    private $model;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->indexProcessor = $this->createMock(IndexProcessor::class);
        $this->model = new UpdateIndexSalabilityStatus(
            $this->indexProcessor,
        );
    }

    public function testExecute(): void
    {
        $stockId = 2;
        $skus = ['P1', 'P2'];
        $changes = ['P1' => true];

        $reservation = new ReservationData($skus, $stockId);
        $this->indexProcessor->expects(self::once())
            ->method('execute')
            ->with($reservation)
            ->willReturn($changes);

        self::assertEquals($changes, $this->model->execute($reservation));
    }
}
