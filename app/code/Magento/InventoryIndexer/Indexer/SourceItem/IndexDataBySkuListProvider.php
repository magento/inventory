<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SelectNotManagableBuilder;

/**
 * Returns all data for the index by SKU List condition
 */
class IndexDataBySkuListProvider
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
     * @param array $skuList
     * @return ArrayIterator
     */
    public function execute(int $stockId, array $skuList): ArrayIterator
    {
        $selectManagable = $this->selectManagableBuilder->execute($stockId);

        if (count($skuList)) {
            $selectManagable->where('source_item.' . SourceItemInterface::SKU . ' IN (?)', $skuList);
        }

        $selectNotManagable = $this->selectNotManagableBuilder->execute($stockId);
        if (count($skuList)) {
            $selectNotManagable->where('product.' . SourceItemInterface::SKU . ' IN (?)', $skuList);
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->union(array($selectManagable, $selectNotManagable));
        return new ArrayIterator($connection->fetchAll($select));
    }
}
