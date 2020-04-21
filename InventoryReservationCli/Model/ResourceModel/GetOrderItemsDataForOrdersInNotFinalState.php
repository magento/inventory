<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;
use Magento\InventoryReservationCli\Model\StoreWebsiteResolver;

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
     * @var StoreWebsiteResolver|null
     */
    private $storeWebsiteResolver;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     * @param StoreWebsiteResolver|null $storeWebsiteResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetCompleteOrderStateList $getCompleteOrderStateList,
        ?StoreWebsiteResolver $storeWebsiteResolver = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
        $this->storeWebsiteResolver = $storeWebsiteResolver
            ?? ObjectManager::getInstance()->get(StoreWebsiteResolver::class);
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
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');
        $orderItemTableName = $this->resourceConnection->getTableName('sales_order_item', 'sales');

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
        $orderItems = $connection->fetchAll($query);
        foreach ($orderItems as $key => $orderItem) {
            $orderItem['website_id'] = $this->storeWebsiteResolver->execute((int) $orderItem['store_id']);
            $orderItems[$key] = $orderItem;
        }
        return $orderItems;
    }
}
