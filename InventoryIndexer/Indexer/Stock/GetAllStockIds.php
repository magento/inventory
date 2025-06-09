<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\Stock as StockResourceModel;
use Magento\Inventory\Model\StockSourceLink;

/**
 * Returns all stock ids.
 */
class GetAllStockIds
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Fetches all stock IDs from the database by querying the stock table and returns them as an integer array.
     *
     * @return int[]
     */
    public function execute(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $stockTable = $this->resourceConnection->getTableName(StockResourceModel::TABLE_NAME_STOCK);

        $select = $connection->select()->from($stockTable, StockSourceLink::STOCK_ID);

        $stockIds = $connection->fetchCol($select);
        $stockIds = array_map('intval', $stockIds);

        return $stockIds;
    }
}
