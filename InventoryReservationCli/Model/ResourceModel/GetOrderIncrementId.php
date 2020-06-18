<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get order increment id by entity id
 */
class GetOrderIncrementId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get increment id by entity id
     *
     * @param int $entityId
     * @return string
     */
    public function execute(int $entityId): string
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                ['main_table.increment_id']
            )
            ->where('main_table.entity_id = ?', $entityId);
        return (string)$connection->fetchOne($query);
    }
}
