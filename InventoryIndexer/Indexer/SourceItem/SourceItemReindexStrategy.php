<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryIndexer\Model\IndexerConfig;

/**
 * Receiver of currently active reindex strategy for source items
 *
 * @api
 */
class SourceItemReindexStrategy
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
     * Reindex given source items.
     *
     * @param array $sourceItemIds
     * @return void
     * @throws LocalizedException
     */
    public function executeList(array $sourceItemIds): void
    {
        $this->getStrategy()->executeList($sourceItemIds);
    }

    /**
     * Reindex all source items.
     *
     * @return void
     * @throws LocalizedException
     */
    public function executeFull(): void
    {
        $this->getStrategy()->executeFull();
    }

    /**
     * Reindex given source item.
     *
     * @param int $sourceItemId
     * @return void
     * @throws LocalizedException
     */
    public function executeRow(int $sourceItemId): void
    {
        $this->getStrategy()->executeList([$sourceItemId]);
    }

    /**
     * Retrieve enabled strategy for reindex.
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function getStrategy()
    {
        $enabledStrategy = $this->indexerConfig->getActiveIndexStrategy();
        if (!isset($this->strategies[$enabledStrategy])) {
            throw new LocalizedException(__("Index Strategy not found, please check system settings."));
        }

        return $this->objectManager->get($this->strategies[$enabledStrategy]);
    }
}
