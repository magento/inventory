<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;

/**
 * Handles reindexing of source items after they are deleted by intercepting the execution
 * and triggering the source indexer.
 */
class ReindexAfterSourceItemsDeletePlugin
{
    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param GetSkuListInStock $getSkuListInStock
     * @param SkuListsProcessor $skuListsProcessor
     */
    public function __construct(
        private readonly GetSourceItemIds $getSourceItemIds,
        private readonly GetSkuListInStock $getSkuListInStock,
        private readonly SkuListsProcessor $skuListsProcessor,
    ) {
    }

    /**
     * Reindexes source items after deletion by intercepting execution and triggering the source indexer.
     *
     * @param SourceItemsDeleteInterface $subject
     * @param callable $proceed
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SourceItemsDeleteInterface $subject,
        callable $proceed,
        array $sourceItems
    ) {
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        $skuListInStockList = $this->getSkuListInStock->execute($sourceItemIds);

        $proceed($sourceItems);

        if ($skuListInStockList) {
            $this->skuListsProcessor->reindexList($skuListInStockList);
        }
    }
}
