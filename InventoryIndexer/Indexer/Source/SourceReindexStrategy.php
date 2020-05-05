<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Source;

use Magento\InventoryIndexer\Model\IndexerConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Receiver of currently active reindex strategy for source items
 *
 * @api
 */
class SourceReindexStrategy implements SourceReindexStrategyInterface
{

    /**
     * @var SourceIndexerInterface[]
     */
    private $indexers;

    /**
     * @var IndexerConfig
     */
    private $indexerConfig;

    /**
     * ReindexStrategy constructor
     *
     * @param IndexerConfig $indexerConfig
     * @param SourceIndexerInterface[] $indexers
     */
    public function __construct(
        IndexerConfig $indexerConfig,
        $indexers = []
    ) {
        $this->indexers = $indexers;
        $this->indexerConfig = $indexerConfig;
    }

    /**
     * @inheritdoc
     */
    public function getStrategy(): SourceIndexerInterface
    {
        $enabledStrategy = $this->indexerConfig->getActiveSourceIndexStrategy();
        if (!isset($this->indexers[$enabledStrategy])) {
            throw new LocalizedException(__("Index Strategy not found, please check system settings."));
        }
        return $this->indexers[$enabledStrategy];
    }
}
