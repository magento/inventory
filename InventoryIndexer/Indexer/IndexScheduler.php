<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\InventoryIndexer\Model\IndexerConfig;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

/**
 * Index sheduler is responsible for shedule index update via Message Queue
 */
class IndexScheduler
{

    public const TOPIC_SOURCE_ITEMS_INDEX = "inventory.indexer.sourceItem";
    public const TOPIC_SOURCE_INDEX = "inventory.indexer.source";

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var IndexerConfig
     */
    private $indexerConfig;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * IndexScheduler constructor.
     *
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher,
        IndexerConfig $indexerConfig,
        SourceItemIndexer $sourceItemIndexer,
        SourceIndexer $sourceIndexer
    ) {
        $this->publisher = $publisher;
        $this->indexerConfig = $indexerConfig;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->sourceIndexer = $sourceIndexer;
    }

    /**
     * Shedule list of source items for reindex
     *
     * @param array $sourceItemIds
     * @return void
     */
    public function scheduleSourceItems(array $sourceItemIds)
    {
        if ($this->indexerConfig->isAsyncIndexerEnabled()) {
            $this->publisher->publish(self::TOPIC_SOURCE_ITEMS_INDEX, $sourceItemIds);
        }else{
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
    }

    /**
     * Shedule list of sources for reindex
     *
     * @param array $sourceCodes
     * @return void
     */
    public function scheduleSources(array $sourceCodes)
    {
        if ($this->indexerConfig->isAsyncIndexerEnabled()) {
            $this->publisher->publish(self::TOPIC_SOURCE_INDEX, $sourceCodes);
        }else{
            $this->sourceIndexer->executeList($sourceCodes);
        }
    }

}
