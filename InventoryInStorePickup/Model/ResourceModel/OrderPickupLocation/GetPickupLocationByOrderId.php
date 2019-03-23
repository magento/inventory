<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\OrderPickupLocation;

use Magento\Framework\App\ResourceConnection;

/**
 * Get Pickup Location identifier by order identifier.
 */
class GetPickupLocationByOrderId
{
    private const ORDER_ID = 'order_id';

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
     *
     * @return string|null
     */
    public function execute(int $orderId): ?string
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_location_order');

        $select = $connection->select()
             ->from($table, [self::PICKUP_LOCATION_CODE => self::PICKUP_LOCATION_CODE])
             ->where(self::ORDER_ID . '= ?', $orderId)
             ->limit(1);

        $id = $connection->fetchOne($select);

        return $id ?: null;
    }
}
