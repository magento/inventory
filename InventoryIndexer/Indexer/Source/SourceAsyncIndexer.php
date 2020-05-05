<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Source;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\InventoryIndexer\Indexer\IndexerInterface;

/**
 * Source Asynchronous Item indexer
 *
 * @api
 */
class SourceAsyncIndexer implements SourceIndexerInterface
{
    public const TOPIC_SOURCE_INDEX = "inventory.indexer.source";

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * Source Asynchronous Item indexer constructor
     *
     * @param PublisherInterface $publisher
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        PublisherInterface $publisher,
        StockIndexer $stockIndexer
    ) {
        $this->publisher = $publisher;
        $this->stockIndexer = $stockIndexer;
    }

    /**
     * @return void
     */
    public function executeFull(): void
    {
        $this->stockIndexer->executeFull();
    }

    /**
     * Shedule Reindex of one item by id
     *
     * @param string $sourceCode
     * @return void
     */
    public function executeRow(string $sourceCode): void
    {
        $this->executeList([$sourceCode]);
    }

    /**
     * Shedule Reindex of items list
     *
     * @param array $sourceCodes
     * @return void
     */
    public function executeList(array $sourceCodes): void
    {
        $this->publisher->publish(self::TOPIC_SOURCE_INDEX, $sourceCodes);
    }
}
