<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer\Stock;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryConfigurableProductIndexer\Indexer\SelectBuilder;

/**
 * Returns all data for the index by stock id condition
 */
class IndexDataByStockIdProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SelectBuilder
     */
    private $selectBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SelectBuilder $selectBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectBuilder = $selectBuilder;
    }

    /**
     * Fetches indexed data for a given stock ID using a database query and returns it as an array iterator.
     *
     * @param int $stockId
     * @return ArrayIterator
     */
    public function execute(int $stockId): ArrayIterator
    {
        $select = $this->selectBuilder->execute($stockId);

        $connection = $this->resourceConnection->getConnection();
        return new ArrayIterator($connection->fetchAll($select));
    }
}
