<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupPoint;

use Magento\Framework\App\ResourceConnection;

/**
 * Save Order Pickup Point
 */
class SaveOrderPickupPoint
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
     * @param string $pickupPointId
     *
     * @return void
     */
    public function execute(int $orderId, string $pickupPointId):void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_point_order');

        $data = [
            self::ORDER_ID => $orderId,
            self::PICKUP_POINT_ID => $pickupPointId
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
