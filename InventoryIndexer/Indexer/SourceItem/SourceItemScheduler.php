<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Source Item indexer
 *
 * @api
 */
class SourceItemScheduler
{

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * SourceItemScheduler constructor.
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
    public function scheduleList(array $sourceItemIds)
    {
        $this->publisher->publish('inventory.stockItem.indexer', $sourceItemIds);
    }
}
