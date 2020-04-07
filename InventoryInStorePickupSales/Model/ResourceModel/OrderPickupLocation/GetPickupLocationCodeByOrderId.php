<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderPickupLocation;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Get Pickup Location identifier by order identifier.
 */
class GetPickupLocationCodeByOrderId
{
    private const ORDER_ID = 'order_id';

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
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
        $connection = $this->connection->getConnection('sales');
        $table = $this->connection->getTableName('inventory_pickup_location_order', 'sales');

        $columns = [PickupLocationInterface::PICKUP_LOCATION_CODE => PickupLocationInterface::PICKUP_LOCATION_CODE];
        $select = $connection->select()
                             ->from($table, $columns)
                             ->where(self::ORDER_ID . '= ?', $orderId)
                             ->limit(1);

        $id = $connection->fetchOne($select);

        return $id ?: null;
    }
}
