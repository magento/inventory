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
     * Load order data for order, which are in final state
     *
     * @param array $orderIds
     * @return array
     */
    public function execute(array $orderIds): array
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');

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
            ->where('main_table.entity_id IN (?)', $orderIds)
            ->where('main_table.state IN (?)', $this->getCompleteOrderStateList->execute());

        $orders = $connection->fetchAll($query);
        foreach ($orders as $key => $order) {
            $order['website_id'] = $this->storeWebsiteResolver->execute((int) $order['store_id']);
            $orders[$key] = $order;
        }
        return $orders;
    }
}
