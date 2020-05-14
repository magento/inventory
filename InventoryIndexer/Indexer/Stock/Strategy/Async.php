<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryIndexer\Indexer\Stock\Strategy;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\InventoryIndexer\Indexer\Stock\GetAllStockIds;

/**
 * Reindex stocks asynchronously.
 */
class Async
{
    /**
     * Queue topic name.
     */
    private const TOPIC_STOCK_INDEX = "inventory.indexer.stock";

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var GetAllStockIds
     */
    private $getAllStockIds;

    /**
     * @param GetAllStockIds $getAllStockIds
     * @param PublisherInterface $publisher
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        PublisherInterface $publisher
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->publisher = $publisher;
    }

    /**
     * Schedule full stock reindex.
     *
     * @return void
     */
    public function executeFull(): void
    {
        $stockIds = $this->getAllStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * Schedule reindex of one item by id.
     *
     * @param int $stockId
     * @return void
     */
    public function executeRow(int $stockId): void
    {
        $this->executeList([$stockId]);
    }

    /**
     * Schedule reindex of stocks list.
     *
     * @param array $stockIds
     * @return void
     */
    public function executeList(array $stockIds): void
    {
        $stockIds = array_map('intval', $stockIds);
        $this->publisher->publish(self::TOPIC_STOCK_INDEX, $stockIds);
    }
}
