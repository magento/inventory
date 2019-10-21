<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get count of all existing orders
 */
class GetOrdersTotalCount
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get count of all existing orders
     *
     * @return int
     */
    public function execute(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTableName = $this->resourceConnection->getTableName('sales_order');

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                ['count' => new \Zend_Db_Expr('COUNT(main_table.entity_id)')]
            );
        return (int)$connection->fetchOne($query);
    }
}
