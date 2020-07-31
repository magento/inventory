<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderNotification;

use Magento\Framework\App\ResourceConnection;

/**
 * Save order notification status resource.
 */
class SaveOrderNotification
{
    private const ORDER_ID  = 'order_id';
    private const SEND_NOTIFICATION = 'send_notification';
    private const NOTIFICATION_SENT = 'notification_sent';

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
     * Save 'send notification' and 'notification_sent' for given order id.
     *
     * @param int $orderId
     * @param int $sendNotification
     * @param int $notificationSent
     * @return void
     */
    public function execute(int $orderId, int $sendNotification = 0, int $notificationSent = 0): void
    {
        $connection = $this->connection->getConnection('sales');
        $table = $this->connection->getTableName('inventory_order_notification', 'sales');
        $data = [
            self::ORDER_ID => $orderId,
            self::SEND_NOTIFICATION => $sendNotification,
            self::NOTIFICATION_SENT => $notificationSent
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
