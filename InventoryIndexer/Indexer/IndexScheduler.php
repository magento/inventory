<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\MessageQueue\PublisherInterface;

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
     * IndexScheduler constructor.
     *
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * Shedule list of source items for reindex
     *
     * @param array $sourceItemIds
     * @return void
     */
    public function scheduleSourceItems(array $sourceItemIds)
    {
        $this->publisher->publish(self::TOPIC_SOURCE_ITEMS_INDEX, $sourceItemIds);
    }

    /**
     * Shedule list of sources for reindex
     *
     * @param array $sourceCodes
     * @return void
     */
    public function scheduleSources(array $sourceCodes)
    {
        $this->publisher->publish(self::TOPIC_SOURCE_ITEMS_INDEX, $sourceCodes);
    }
}
