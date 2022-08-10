<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Class for getting stock names by stock ids.
 */
class GetStockNamesByIds
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
     * Get stock names by ids.
     *
     * @param array $stockIds
     * @return array
     */
    public function execute(array $stockIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('inventory_stock'),
                [StockInterface::STOCK_ID, StockInterface::NAME]
            )
            ->where(
                StockInterface::STOCK_ID . ' IN (?)',
                $stockIds
            );
        $rows = $connection->fetchAll($select);
        $stockNames = array_column($rows, StockInterface::NAME, StockInterface::STOCK_ID);

        return $stockNames;
    }
}
