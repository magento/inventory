<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\IndexStructure;

/**
 * Returns all data for the index by source item list condition.
 */
class IndexDataBySkuListProvider
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
     * Get index data by stock id and sku list.
     *
     * @param int $stockId
     * @param array $skuList
     * @return \ArrayIterator
     * @throws \Exception
     */
    public function execute(int $stockId, array $skuList): \ArrayIterator
    {
        $select = $this->selectBuilder->execute($stockId);
        if (count($skuList)) {
            $select->where('stock.' . IndexStructure::SKU . ' IN (?)', $skuList);
        }
        $connection = $this->resourceConnection->getConnection();

        return new \ArrayIterator($connection->fetchAll($select));
    }
}
