<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\ReservationDataFactory;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\UpdateLegacyStock;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;
use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UpdateIndexSalabilityStatus
 */
class UpdateIndexSalabilityStatusTest extends TestCase
{
    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private $defaultStockProvider;

    /**
     * @var IndexProcessor|MockObject
     */
    private $indexProcessor;

    /**
     * @var UpdateLegacyStock|MockObject
     */
    private $updateLegacyStock;

    /**
     * @var GetParentSkusOfChildrenSkusInterface|MockObject
     */
    private $getParentSkusOfChildrenSkus;

    /**
     * @var ReservationDataFactory|MockObject
     */
    private $reservationDataFactory;

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
        $this->defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $this->defaultStockProvider->method('getId')
            ->willReturn(1);
        $this->indexProcessor = $this->createMock(IndexProcessor::class);
        $this->updateLegacyStock = $this->createMock(UpdateLegacyStock::class);
        $this->getParentSkusOfChildrenSkus = $this->createMock(GetParentSkusOfChildrenSkusInterface::class);
        $this->reservationDataFactory = $this->createMock(ReservationDataFactory::class);
        $this->model = new UpdateIndexSalabilityStatus(
            $this->defaultStockProvider,
            $this->indexProcessor,
            $this->updateLegacyStock,
            $this->getParentSkusOfChildrenSkus,
            $this->reservationDataFactory
        );
    }

    /**
     * Test that legacy stock indexer is executed if the stock is default otherwise custom stock indexer is executed
     *
     * @param int $stockId
     * @param int $updateLegacyStockInvokeCount
     * @param int $indexProcessorInvokeCount
     * @param array $parentSkusOfChildrenSkus
     * @param array $affectedParentSkus
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        int $stockId,
        int $updateLegacyStockInvokeCount,
        int $indexProcessorInvokeCount,
        array $parentSkusOfChildrenSkus,
        array $affectedParentSkus
    ): void {
        $skus = ['P1', 'P2'];
        $changes = ['P1' => true];
        $parentChanges = array_fill_keys($affectedParentSkus, true);
        $changes = array_merge($changes, $parentChanges);

        $reservation = new ReservationData($skus, $stockId);
        $this->updateLegacyStock->expects($this->exactly($updateLegacyStockInvokeCount))
            ->method('execute')
            ->with($reservation)
            ->willReturn($changes);
        $this->indexProcessor->expects($this->exactly($indexProcessorInvokeCount))
            ->method('execute')
            ->willReturn($changes);
        $this->getParentSkusOfChildrenSkus->method('execute')
            ->willReturn($parentSkusOfChildrenSkus);
        $reservationData = $this->createMock(ReservationData::class);
        $this->reservationDataFactory->method('create')
            ->willReturn($reservationData);

        $this->assertEquals($changes, $this->model->execute($reservation));
    }

    /**
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                'stockId' => 1,
                'updateLegacyStockInvokeCount' => 1,
                'indexProcessorInvokeCount' => 0,
                'parentSkusOfChildrenSkus' => [],
                'affectedParentSkus' => [],
            ],
            [
                'stockId' => 2,
                'updateLegacyStockInvokeCount' => 0,
                'indexProcessorInvokeCount' => 2,
                'parentSkusOfChildrenSkus' => [
                    'P1' => ['PConf1', 'PConf2']
                ],
                'affectedParentSkus' => ['PConf1', 'PConf2'],
            ],
            [
                'stockId' => 3,
                'updateLegacyStockInvokeCount' => 0,
                'indexProcessorInvokeCount' => 2,
                'parentSkusOfChildrenSkus' => [
                    'P1' => ['PConf3']
                ],
                'affectedParentSkus' => ['PConf3'],
            ],
        ];
    }
}
