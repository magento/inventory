<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\Exception\LocalizedException;

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
    public function __construct(StockReindexStrategy $stockReindexStrategy)
    {
        $this->stockReindexStrategy = $stockReindexStrategy;
    }

    /**
     * Reindex all stocks.
     *
     * @return void
     * @throws LocalizedException
     */
    public function executeFull()
    {
        $this->stockReindexStrategy->executeFull();
    }

    /**
     * Reindex given stock.
     *
     * @param int $stockId
     * @return void
     * @throws LocalizedException
     */
    public function executeRow(int $stockId)
    {
        $this->stockReindexStrategy->executeRow($stockId);
    }

    /**
     * Reindex given stocks.
     *
     * @param array $stockIds
     * @return void
     * @throws LocalizedException
     */
    public function executeList(array $stockIds)
    {
        $this->stockReindexStrategy->executeList($stockIds);
    }
}
