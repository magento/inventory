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
 * Load order data for order, which are in final state
 */
class GetOrderDataForOrderInFinalState
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
     * Load order data for order, which are in final state
     *
     * @param array $orderIds
     * @return array
     */
    public function execute(array $orderIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        $storeTableName = $this->resourceConnection->getTableName('store');

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                [
                    'main_table.entity_id',
                    'main_table.status',
                    'main_table.increment_id',
                ]
            )
            ->join(
                ['store' => $storeTableName],
                'store.store_id = main_table.store_id',
                ['store.website_id']
            )
            ->where('main_table.entity_id IN (?)', $orderIds)
            ->where('main_table.state IN (?)', $this->getCompleteOrderStateList->execute());

        return $connection->fetchAll($query);
    }
}
