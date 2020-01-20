<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderNotificationSent;

use Magento\Framework\App\ResourceConnection;

/**
 * Save 'notification sent' resource.
 */
class SaveOrderNotificationSent
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
     * Save 'notification sent' for given order id.
     *
     * @param int $orderId
     * @param int $notificationSent
     *
     * @return void
     */
    public function execute(int $orderId, int $notificationSent): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_order_notification');
        $data = [
            self::ORDER_ID => $orderId,
            'send_notification' => $notificationSent
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
