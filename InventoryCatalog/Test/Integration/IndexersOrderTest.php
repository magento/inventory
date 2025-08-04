<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Framework\Indexer\Config\Converter\SortingAdjustmentInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexer;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class IndexersOrderTest extends TestCase
{
    /**
     * @var SortingAdjustmentInterface
     */
    private $sortingAdjustment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sortingAdjustment = Bootstrap::getObjectManager()->create(SortingAdjustmentInterface::class);
    }

    /**
     * @return void
     */
    public function testIndexersOrder()
    {
        $unAdjusted = [
            'indexer1' => [],
            PriceIndexer::INDEXER_ID => [],
            'indexer2' => [],
            InventoryIndexer::INDEXER_ID => [],
            'indexer3' => [],
            StockIndexer::INDEXER_ID => [],
            'indexer4' => []
        ];
        $output = $this->sortingAdjustment->adjust($unAdjusted);
        $this->assertArrayHasKey(PriceIndexer::INDEXER_ID, $output);
        $this->assertArrayHasKey(InventoryIndexer::INDEXER_ID, $output);
        $this->assertArrayHasKey(StockIndexer::INDEXER_ID, $output);
        $order = array_keys($output);
        $inventoryPos = array_search(InventoryIndexer::INDEXER_ID, $order);
        $stockPos = array_search(StockIndexer::INDEXER_ID, $order);
        $pricePos = array_search(PriceIndexer::INDEXER_ID, $order);
        $this->assertTrue($stockPos < $inventoryPos);
        $this->assertTrue($inventoryPos < $pricePos);
    }
}
