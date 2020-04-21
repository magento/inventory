<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Sales\Model\ResourceModel\Order;

/**
 * Get count of all existing orders
 */
class GetOrdersTotalCount
{
    /**
     * @var Order
     */
    private $orderResourceModel;

    /**
     * @param Order $orderResourceModel
     */
    public function __construct(Order $orderResourceModel)
    {
        $this->orderResourceModel = $orderResourceModel;
    }

    /**
     * Get count of all existing orders
     *
     * @return int
     */
    public function execute(): int
    {
        $connection = $this->orderResourceModel->getConnection();
        $orderTableName = $this->orderResourceModel->getMainTable();
        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                ['count' => new \Zend_Db_Expr('COUNT(main_table.entity_id)')]
            );
        return (int)$connection->fetchOne($query);
    }
}
