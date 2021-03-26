<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
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
     * @var GetAllowedProductTypesForSourceItemManagementInterface|null
     */
    private $allowedProductTypesForSourceItemManagement;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     * @param GetAllowedProductTypesForSourceItemManagementInterface|null $allowedProductTypesForSourceItemManagement
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetCompleteOrderStateList $getCompleteOrderStateList,
        GetAllowedProductTypesForSourceItemManagementInterface $allowedProductTypesForSourceItemManagement = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
        $this->allowedProductTypesForSourceItemManagement = $allowedProductTypesForSourceItemManagement
            ?: ObjectManager::getInstance()->get(GetAllowedProductTypesForSourceItemManagementInterface::class);
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
                [
                    'item.sku',
                    'item.is_virtual',
                    'item.qty_ordered',
                    'item.qty_canceled',
                    'item.qty_invoiced',
                    'item.qty_refunded',
                    'item.qty_shipped'
                ]
            )
            ->where('main_table.entity_id IN (?)', $entityIds)
            ->where('item.product_type IN (?)', $this->allowedProductTypesForSourceItemManagement->execute());
        $orderItems = $connection->fetchAll($query);
        $storeWebsiteIds = $this->getStoreWebsiteIds();
        foreach ($orderItems as $key => $orderItem) {
            $orderItem['website_id'] = $storeWebsiteIds[$orderItem['store_id']];
            $orderItems[$key] = $orderItem;
        }
        return $orderItems;
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
