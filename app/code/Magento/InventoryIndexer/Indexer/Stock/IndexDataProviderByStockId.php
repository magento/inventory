<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SelectNotManagableBuilder;

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
     * @var SelectManagableBuilder
     */
    private $selectManagableBuilder;

    /**
     * @var SelectNotManagableBuilder
     */
    private $selectNotManagableBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SelectBuilder $selectManagableBuilder,
        SelectNotManagableBuilder $selectNotManagableBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectManagableBuilder = $selectManagableBuilder;
        $this->selectNotManagableBuilder = $selectNotManagableBuilder;
    }

    /**
     * @param int $stockId
     * @return ArrayIterator
     */
    public function execute(int $stockId): ArrayIterator
    {
        $selectManagable = $this->selectManagableBuilder->execute($stockId);
        $selectNotManagable = $this->selectNotManagableBuilder->execute($stockId);

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->union(array($selectManagable, $selectNotManagable));
        return new ArrayIterator($connection->fetchAll($select));
    }
}
