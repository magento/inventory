<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\SourceSelection;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Save Order Pickup Location by Order Id.
 */
class GetActiveStorePickupOrdersBySource
{
    private const ORDER_ID  = 'order_id';

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var array
     */
    private $statesToFilter;

    /**
     * @param ResourceConnection $connection
     * @param array $statesToFilter
     */
    public function __construct(
        ResourceConnection $connection,
        array $statesToFilter = []
    ) {
        $this->connection = $connection;
        $this->statesToFilter = $statesToFilter;
    }

    /**
     * ets list of orders ids placed by store pickup which are not complete yet.
     *
     * @param string $pickupLocationCode
     *
     * @return array
     */
    public function execute(string $pickupLocationCode): array
    {
        $connection = $this->connection->getConnection('sales');
        $table1 = $this->connection->getTableName('sales_order', 'sales');
        $table2 = $this->connection->getTableName('inventory_pickup_location_order', 'sales');
        $select = $connection->select()
            ->from($table1, 'entity_id')
            ->joinLeft($table2, 'sales_order.entity_id = ' . self::ORDER_ID)
            ->where(
                'inventory_pickup_location_order.' . PickupLocationInterface::PICKUP_LOCATION_CODE,
                $pickupLocationCode
            )
            ->where(OrderInterface::STATE . ' NOT IN (?)', implode(',', $this->statesToFilter));

        return $connection->fetchAll($select);
    }
}
