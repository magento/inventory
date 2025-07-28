<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem\Strategy;

use Magento\InventoryIndexer\Indexer\SourceItem\GetSkuListInStock;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;

/**
 * Reindex source items synchronously.
 */
class Sync
{
    /**
     * $indexStructure is reserved name for construct variable (in index internal mechanism)
     *
     * @param GetSkuListInStock $getSkuListInStock
     * @param StockIndexer $stockIndexer
     * @param SkuListsProcessor $skuListsProcessor
     */
    public function __construct(
        private readonly GetSkuListInStock $getSkuListInStock,
        private readonly StockIndexer $stockIndexer,
        private readonly SkuListsProcessor $skuListsProcessor,
    ) {
    }

    /**
     * Reindex list of source items by provided ids.
     *
     * @param int[] $sourceItemIds
     */
    public function executeList(array $sourceItemIds) : void
    {
        $skuListInStockList = $this->getSkuListInStock->execute($sourceItemIds);
        $this->skuListsProcessor->reindexList($skuListInStockList);
    }

    /**
     * Reindex all source items
     *
     * @return void
     */
    public function executeFull() : void
    {
        $this->stockIndexer->executeFull();
    }

    /**
     * Reindex single source item by id
     *
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId) : void
    {
        $this->executeList([$sourceItemId]);
    }
}
