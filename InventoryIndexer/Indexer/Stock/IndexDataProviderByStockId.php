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
use Magento\InventoryConfigurableProductIndexer\Indexer\SelectBuilder as LinkedSelectBuilder;

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
     * @var SelectBuilder
     */
    private $selectBuilder;

    /**
     * @var LinkedSelectBuilder
     */
    private $linkedSelectBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     * @param LinkedSelectBuilder $linkedSelectBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SelectBuilder $selectBuilder,
        LinkedSelectBuilder $linkedSelectBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectBuilder = $selectBuilder;
        $this->linkedSelectBuilder = $linkedSelectBuilder;
    }

    /**
     * @param int $stockId
     * @throws \Exception
     * @return ArrayIterator
     */
    public function execute(int $stockId): ArrayIterator
    {
        $result = [];
        $select = $this->selectBuilder->execute($stockId);

        $connection = $this->resourceConnection->getConnection();
        $result = array_merge($result, $connection->fetchAll($select));
        $linkedSelect = $this->linkedSelectBuilder->execute($stockId);
        $result = array_merge($result, $connection->fetchAll($linkedSelect));
        return new ArrayIterator($result);
    }
}
