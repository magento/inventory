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
 * Loads order item data for orders, which are not in final state
 */
class GetOrderItemsDataForOrdersInNotFinalState
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
     * Load reservations from database.
     *
     * @param int $bunchSize
     * @param int $page
     * @return array
     */
    public function execute(int $bunchSize = 50, int $page = 1): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        $orderItemTableName = $this->resourceConnection->getTableName('sales_order_item');
        $storeTableName = $this->resourceConnection->getTableName('store');

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
                    'main_table.status'
                ]
            )
            ->join(
                ['store' => $storeTableName],
                'store.store_id = main_table.store_id',
                ['store.website_id']
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
