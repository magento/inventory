<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderNotification;

use Magento\Framework\App\ResourceConnection;

/**
 * Get order 'notification sent' by order identifier resource.
 */
class GetOrderNotificationSentByOrderId
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
     * Fetch 'notification sent' identifier by order identifier.
     *
     * @param int $orderId
     * @return int|null
     */
    public function execute(int $orderId): ?int
    {
        $connection = $this->connection->getConnection('sales');
        $table = $this->connection->getTableName('inventory_order_notification', 'sales');
        $select = $connection->select()
            ->from($table, 'notification_sent')
            ->where(self::ORDER_ID . '= ?', $orderId)
            ->limit(1);
        $sendNotification = $connection->fetchOne($select);

        return $sendNotification !== false ? (int)$sendNotification : null;
    }
}
