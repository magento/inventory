<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupLocation;

use Magento\Framework\App\ResourceConnection;

/**
 * Save Order Pickup Location
 */
class SaveOrderPickupLocation
{
    private const ORDER_ID  = 'order_id';
    private const PICKUP_LOCATION_CODE = 'pickup_location_code';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $connection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $connection
     */
    public function __construct(
        ResourceConnection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Fetch pickup location identifier by order identifier.
     *
     * @param int $orderId
     * @param string $pickupLocationCode
     *
     * @return void
     */
    public function execute(int $orderId, string $pickupLocationCode): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_location_order');

        $data = [
            self::ORDER_ID => $orderId,
            self::PICKUP_LOCATION_CODE => $pickupLocationCode
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
