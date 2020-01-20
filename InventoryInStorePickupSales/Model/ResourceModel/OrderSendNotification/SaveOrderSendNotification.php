<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderSendNotification;

use Magento\Framework\App\ResourceConnection;

/**
 * Save 'send notification' resource.
 */
class SaveOrderSendNotification
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
     * Save 'send notification' for given order id.
     *
     * @param int $orderId
     * @param int $sendNotification
     *
     * @return void
     */
    public function execute(int $orderId, int $sendNotification): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_order_notification');
        $data = [
            self::ORDER_ID => $orderId,
            'send_notification' => $sendNotification
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
