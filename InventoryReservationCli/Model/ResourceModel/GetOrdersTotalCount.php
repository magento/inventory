<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;

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
     * @var GetCompleteOrderStateList
     */
    private $getCompleteOrderStateList;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetCompleteOrderStateList $getCompleteOrderStateList
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
    }

    /**
     * Get count of all existing orders
     *
     * @return int
     */
    public function execute(): int
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');
        $query = $connection->select()
            ->from(
                ['main_table' => $orderTableName],
                ['count' => new \Zend_Db_Expr('COUNT(main_table.entity_id)')]
            )->where(
                'main_table.state NOT IN (?)',
                $this->getCompleteOrderStateList->execute()
            );

        return (int)$connection->fetchOne($query);
    }
}
