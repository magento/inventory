<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;

/**
 * Returns all data for the index
 */
class IndexDataProviderByStockId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $selectBuilders;

    /**
     * @param ResourceConnection $resourceConnection
     * @param array $selectBuilders
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        array $selectBuilders
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectBuilders = $selectBuilders;
    }

    /**
     * Returns selected data
     *
     * @param int $stockId
     * @throws \Exception
     * @return ArrayIterator
     */
    public function execute(int $stockId): ArrayIterator
    {
        $result = [];
        $connection = $this->resourceConnection->getConnection();

        foreach ($this->selectBuilders as $selectBuilder) {
            $select = $selectBuilder->execute($stockId);
            $result[] = $connection->fetchAll($select);
        }

        return new ArrayIterator(array_merge([], ...$result));
    }
}
