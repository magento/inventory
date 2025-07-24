<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Source;

use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;

/**
 * Represents the Source Indexer, responsible for executing full, single, or multiple source indexing operations.
 *
 * @api
 */
class SourceIndexer
{
    /**
     * @var GetAssignedStockIds
     */
    private $getAssignedStockIds;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @param GetAssignedStockIds $getAssignedStockIds
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        GetAssignedStockIds $getAssignedStockIds,
        StockIndexer $stockIndexer
    ) {
        $this->getAssignedStockIds = $getAssignedStockIds;
        $this->stockIndexer = $stockIndexer;
    }

    /**
     * Triggers a full reindexing operation for all stock data using the stock indexer.
     *
     * @return void
     */
    public function executeFull()
    {
        $this->stockIndexer->executeFull();
    }

    /**
     * Performs reindexing for a single source code by delegating to the executeList method.
     *
     * @param string $sourceCode
     * @return void
     */
    public function executeRow(string $sourceCode)
    {
        $this->executeList([$sourceCode]);
    }

    /**
     * Reindexes stock data for a list of source codes by fetching stock IDs and invoking the stock indexer.
     *
     * @param array $sourceCodes
     */
    public function executeList(array $sourceCodes)
    {
        $stockIds = $this->getAssignedStockIds->execute($sourceCodes);
        $this->stockIndexer->executeList($stockIds);
    }
}
