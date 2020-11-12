<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\UpdateLegacyStock;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;
use Magento\InventoryCatalogApi\Model\GetParentSkusByChildrenSkusInterface;
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
     * @var GetParentSkusByChildrenSkusInterface|MockObject
     */
    private $getParentSkusByChildrenSkus;

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
        $this->getParentSkusByChildrenSkus = $this->createMock(GetParentSkusByChildrenSkusInterface::class);
        $this->model = new UpdateIndexSalabilityStatus(
            $this->defaultStockProvider,
            $this->indexProcessor,
            $this->updateLegacyStock,
            $this->getParentSkusByChildrenSkus
        );
    }

    /**
     * Test that legacy stock indexer is executed if the stock is default otherwise custom stock indexer is executed
     *
     * @param int $stockId
     * @param int $updateLegacyStockInvokeCount
     * @param int $indexProcessorInvokeCount
     * @param array $parentSkus
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        int $stockId,
        int $updateLegacyStockInvokeCount,
        int $indexProcessorInvokeCount,
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
            ->with($reservation, $stockId)
            ->willReturn($changes);
        $this->getParentSkusByChildrenSkus->method('execute')
            ->willReturn($affectedParentSkus);

        $this->assertEquals($changes, $this->model->execute($reservation));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'stock_id' => 1,
                'update_legacy_stock_invoke_count' => 1,
                'index_processor_invoke_count' => 0,
                'affected_parent_skus' => []
            ],
            [
                'stock_id' => 2,
                'update_legacy_stock_invoke_count' => 0,
                'index_processor_invoke_count' => 1,
                'affected_parent_skus' => ['PConf1', 'PConf2']
            ],
            [
                'stock_id' => 3,
                'update_legacy_stock_invoke_count' => 0,
                'index_processor_invoke_count' => 1,
                'affected_parent_skus' => ['PConf3']
            ],
        ];
    }
}
