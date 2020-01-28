<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;

/**
 * Bundle products for given stock provider.
 */
class IndexDataByStockIdProvider
{
    /**
     * @var SelectBuilder
     */
    private $selectBuilder;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param SelectBuilder $selectBuilder
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(SelectBuilder $selectBuilder, ResourceConnection $resourceConnection)
    {
        $this->selectBuilder = $selectBuilder;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get bundle products for given stock id.
     *
     * @param int $stockId
     *
     * @return \ArrayIterator
     * @throws \Exception
     */
    public function execute(int $stockId): \ArrayIterator
    {
        $select = $this->selectBuilder->execute($stockId);
        $connection = $this->resourceConnection->getConnection();

        return new \ArrayIterator($connection->fetchAll($select));
    }
}
