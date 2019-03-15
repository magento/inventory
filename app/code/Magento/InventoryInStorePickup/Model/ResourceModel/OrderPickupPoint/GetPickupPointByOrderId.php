<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupPoint;

use Magento\Framework\App\ResourceConnection;

/**
 * Get Pickup Point identifier by order identifier.
 */
class GetPickupPointByOrderId
{
    private const ORDER_ID        = 'order_id';
    private const PICKUP_POINT_ID = 'pickup_point_id';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $connection;

    /**
     * GetPickupPointByOrderId constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $connection
     */
    public function __construct(
        ResourceConnection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Fetch pickup point identifier by order identifier.
     *
     * @param int $orderId
     *
     * @return string|null
     */
    public function execute(int $orderId):?string
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_point_order');

        $select = $connection->select()
             ->from($table, [
                     self::PICKUP_POINT_ID => self::PICKUP_POINT_ID
             ])
             ->where(self::ORDER_ID . '= ?', $orderId)
             ->limit(1);

        $id = $connection->fetchOne($select);

        return $id ?: null;
    }
}
