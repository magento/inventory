<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Item;

/**
 * Loads order item data for orders, which are not in final state
 */
class GetOrderItemsDataForOrdersInNotFinalState
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
     * @var Item
     */
    private $orderItemResourceModel;

    /**
     * @param Order $orderResourceModel
     * @param Item $orderItemResourceModel
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     */
    public function __construct(
        Order $orderResourceModel,
        Item $orderItemResourceModel,
        GetCompleteOrderStateList $getCompleteOrderStateList
    ) {
        $this->orderResourceModel = $orderResourceModel;
        $this->orderItemResourceModel = $orderItemResourceModel;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
    }

    /**
     * Load reservations from database.
     *
     * @param int $bunchSize
     * @param int $page
     * @return array
     */
    public function execute(int $bunchSize = 50, int $page = 1): array
    {
        $connection = $this->orderResourceModel->getConnection();
        $orderTableName = $this->orderResourceModel->getMainTable();
        $orderItemTableName = $this->orderItemResourceModel->getMainTable();

        $orderEntityIdSelectQuery = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                ['main_table.entity_id']
            )
            ->where('main_table.state NOT IN (?)', $this->getCompleteOrderStateList->execute())
            ->limitPage($page, $bunchSize);
        $entityIds = $connection->fetchCol($orderEntityIdSelectQuery);

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                [
                    'main_table.entity_id',
                    'main_table.increment_id',
                    'main_table.status',
                    'main_table.store_id'
                ]
            )
            ->join(
                ['item' => $orderItemTableName],
                'item.order_id = main_table.entity_id',
                ['item.sku', 'item.qty_ordered']
            )
            ->where('main_table.entity_id IN (?)', $entityIds)
            ->where('item.product_type IN (?)', ['simple']);
        return $connection->fetchAll($query);
    }
}
