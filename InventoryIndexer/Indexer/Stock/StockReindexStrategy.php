<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryIndexer\Model\IndexerConfig;

/**
 * Receiver of currently active reindex strategy for stock
 *
 * @api
 */
class StockReindexStrategy
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $strategies;

    /**
     * @var IndexerConfig
     */
    private $indexerConfig;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param IndexerConfig $indexerConfig
     * @param array $strategies
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        IndexerConfig $indexerConfig,
        $strategies = []
    ) {
        $this->objectManager = $objectManager;
        $this->strategies = $strategies;
        $this->indexerConfig = $indexerConfig;
    }

    /**
     * Reindex all stocks.
     *
     * @return void
     * @throws LocalizedException
     */
    public function executeFull(): void
    {
        $strategy = $this->objectManager->get($this->getStrategy());
        $strategy->executeFull();
    }

    /**
     * Reindex given stock.
     *
     * @param int $stockId
     * @return void
     * @throws LocalizedException
     */
    public function executeRow(int $stockId): void
    {
        $strategy = $this->objectManager->get($this->getStrategy());
        $strategy->executeList([$stockId]);
    }

    /**
     * Reindex given stocks.
     *
     * @param array $stockIds
     * @return void
     * @throws LocalizedException
     */
    public function executeList(array $stockIds): void
    {
        $strategy = $this->objectManager->get($this->getStrategy());
        $strategy->executeList($stockIds);
    }

    /**
     * Retrieve reindex strategy.
     *
     * @return string
     * @throws LocalizedException
     */
    private function getStrategy(): string
    {
        $enabledStrategy = $this->indexerConfig->getActiveIndexStrategy();
        if (!isset($this->strategies[$enabledStrategy])) {
            throw new LocalizedException(__("Index Strategy not found, please check system settings."));
        }
        return $this->strategies[$enabledStrategy];
    }
}
