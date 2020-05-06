<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

/**
 * Stock indexer
 * Extension point for indexation
 *
 * @api
 */
class StockIndexer
{
    /**
     * @var StockReindexStrategy
     */
    private $stockReindexStrategy;

    /**
     * @param StockReindexStrategy $stockReindexStrategy
     */
    public function __construct(
        \Magento\InventoryIndexer\Indexer\Stock\StockReindexStrategy $stockReindexStrategy
    ) {
        $this->stockReindexStrategy = $stockReindexStrategy;
    }

    /**
     * @return void
     */
    public function executeFull()
    {
        $this->stockReindexStrategy->executeFull();
    }

    /**
     * @param int $stockId
     * @return void
     */
    public function executeRow(int $stockId)
    {
        $this->stockReindexStrategy->executeRow($stockId);
    }

    /**
     * @param array $stockIds
     * @return void
     */
    public function executeList(array $stockIds)
    {
        $this->stockReindexStrategy->executeList($stockIds);
    }
}
