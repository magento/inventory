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
 * Save Order Pickup Location by Order Id.
 */
class SaveOrderPickupLocation
{
    private const ORDER_ID  = 'order_id';

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
     * @param string $pickupLocationCode
     *
     * @return void
     */
    public function execute(int $orderId, string $pickupLocationCode): void
    {
        $connection = $this->connection->getConnection('sales');
        $table = $this->connection->getTableName('inventory_pickup_location_order', 'sales');

        $data = [
            self::ORDER_ID => $orderId,
            PickupLocationInterface::PICKUP_LOCATION_CODE => $pickupLocationCode
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
