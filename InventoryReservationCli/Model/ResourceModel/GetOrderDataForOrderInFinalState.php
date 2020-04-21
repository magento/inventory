<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;
use Magento\Sales\Model\ResourceModel\Order;

/**
 * Load order data for order, which are in final state
 */
class GetOrderDataForOrderInFinalState
{
    /**
     * @var GetCompleteOrderStateList
     */
    private $getCompleteOrderStateList;
    /**
     * @var Order
     */
    private $orderResourceModel;

    /**
     * @param Order $orderResourceModel
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     */
    public function __construct(
        Order $orderResourceModel,
        GetCompleteOrderStateList $getCompleteOrderStateList
    ) {
        $this->orderResourceModel = $orderResourceModel;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
    }

    /**
     * Load order data for order, which are in final state
     *
     * @param array $orderIds
     * @return array
     */
    public function execute(array $orderIds): array
    {
        $connection = $this->orderResourceModel->getConnection();
        $orderTableName = $this->orderResourceModel->getMainTable();
        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                [
                    'main_table.entity_id',
                    'main_table.status',
                    'main_table.increment_id',
                    'main_table.store_id',
                ]
            )
            ->where('main_table.entity_id IN (?)', $orderIds)
            ->where('main_table.state IN (?)', $this->getCompleteOrderStateList->execute());

        return $connection->fetchAll($query);
    }
}
