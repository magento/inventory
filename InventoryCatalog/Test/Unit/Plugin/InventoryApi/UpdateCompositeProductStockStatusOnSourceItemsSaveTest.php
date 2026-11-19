<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Plugin\InventoryApi\UpdateCompositeProductStockStatusOnSourceItemsSave;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Documents the source-mode gate on the composite parent recompute.
 *
 * The recompute runs only in single source mode. In multi source mode it is skipped, because the parent's
 * legacy stock item is a single default-stock value and recomputing it from default-scope children would
 * let it veto stocks backed by other sources - see magento/inventory#3350.
 */
class UpdateCompositeProductStockStatusOnSourceItemsSaveTest extends TestCase
{
    /**
     * @var IsSingleSourceModeInterface|MockObject
     */
    private $isSingleSourceMode;

    /**
     * @var CompositeProductStockStatusProcessorInterface|MockObject
     */
    private $processor;

    /**
     * @var UpdateCompositeProductStockStatusOnSourceItemsSave
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->isSingleSourceMode = $this->createMock(IsSingleSourceModeInterface::class);
        $this->processor = $this->createMock(CompositeProductStockStatusProcessorInterface::class);
        $this->plugin = new UpdateCompositeProductStockStatusOnSourceItemsSave(
            $this->isSingleSourceMode,
            $this->processor
        );
    }

    /**
     * @return void
     */
    public function testTheParentIsRecomputedInSingleSourceMode(): void
    {
        $this->isSingleSourceMode->method('execute')->willReturn(true);

        $this->processor->expects($this->once())
            ->method('execute')
            ->with(['sku-a', 'sku-b']);

        $this->plugin->afterExecute(
            $this->createMock(SourceItemsSaveInterface::class),
            null,
            [$this->sourceItem('sku-a'), $this->sourceItem('sku-b')]
        );
    }

    /**
     * In multi source mode the recompute is deliberately skipped. See magento/inventory#3350: the parent's
     * legacy row is default-stock data, and recomputing it here makes it veto other stocks.
     *
     * @return void
     */
    public function testTheParentIsNotRecomputedInMultiSourceMode(): void
    {
        $this->isSingleSourceMode->method('execute')->willReturn(false);

        $this->processor->expects($this->never())->method('execute');

        $this->plugin->afterExecute(
            $this->createMock(SourceItemsSaveInterface::class),
            null,
            [$this->sourceItem('sku-a'), $this->sourceItem('sku-b')]
        );
    }

    /**
     * @return void
     */
    public function testAnEmptyBatchRecomputesNothing(): void
    {
        $this->isSingleSourceMode->method('execute')->willReturn(true);
        $this->processor->expects($this->never())->method('execute');

        $this->plugin->afterExecute($this->createMock(SourceItemsSaveInterface::class), null, []);
    }

    /**
     * @param string $sku
     * @return SourceItemInterface|MockObject
     */
    private function sourceItem(string $sku)
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSku')->willReturn($sku);

        return $sourceItem;
    }
}
