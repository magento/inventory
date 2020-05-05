<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryIndexer\Model\IndexerConfig;
use Magento\Framework\Exception\LocalizedException;

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
     * @param array $sourceItemIds
     * @return void
     */
    public function executeList(array $sourceItemIds) : void
    {
        $this->getStrategy()->executeList($sourceItemIds);
    }

    /**
     * @return void
     */
    public function executeFull()
    {
        $this->getStrategy()->executeFull();
    }

    /**
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId)
    {
        $this->getStrategy()->executeList([$sourceItemId]);
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    private function getStrategy()
    {
        $enabledStrategy = $this->indexerConfig->getActiveSourceItemIndexStrategy();
        if (!isset($this->strategies[$enabledStrategy])) {
            throw new LocalizedException(__("Index Strategy not found, please check system settings."));
        }
        return $this->objectManager->get($this->strategies[$enabledStrategy]);
    }
}
