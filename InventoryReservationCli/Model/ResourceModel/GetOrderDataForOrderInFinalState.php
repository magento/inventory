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
     * @param array $orderIncrementIds
     * @return array
     */
    public function execute(array $orderIds, array $orderIncrementIds): array
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');

        $entityIdCondition = $connection->quoteInto('main_table.entity_id IN (?)', $orderIds);
        $incrementIdCondition = $connection->quoteInto('main_table.increment_id IN (?)', $orderIncrementIds);

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                [
                    'main_table.entity_id',
                    'main_table.status',
                    'main_table.increment_id',
                    'main_table.store_id'
                ]
            )
            ->where($entityIdCondition . ' OR ' . $incrementIdCondition)
            ->where('main_table.state IN (?)', $this->getCompleteOrderStateList->execute());

        $orders = $connection->fetchAll($query);
        $storeWebsiteIds = $this->getStoreWebsiteIds();
        foreach ($orders as $key => $order) {
            $order['website_id'] = $storeWebsiteIds[$order['store_id']];
            $orders[$key] = $order;
        }
        return $orders;
    }

    /**
     * Get storeIds with their websiteIds
     *
     * @return array
     */
    private function getStoreWebsiteIds(): array
    {
        $storeWebsiteIds = [];
        $connection = $this->resourceConnection->getConnection();
        $storeTableName = $this->resourceConnection->getTableName('store');
        $query = $connection
            ->select()
            ->from(
                ['main_table' => $storeTableName],
                ['store_id', 'website_id']
            );
        foreach ($connection->fetchAll($query) as $store) {
            $storeWebsiteIds[$store['store_id']] = $store['website_id'];
        }
        return $storeWebsiteIds;
    }
}
