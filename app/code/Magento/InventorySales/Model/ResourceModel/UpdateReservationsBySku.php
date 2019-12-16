<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Update reservation by product sku.
 */
class UpdateReservationsBySku
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(ResourceConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Replace reservations 'sku' value with new one.
     *
     * @param string $origSku
     * @param string $sku
     */
    public function execute(string $origSku, string $sku): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_reservation');
        $bind = ['sku' => (string)$sku];
        $where = ['sku = ?' => (string)$origSku];
        $connection->update($table, $bind, $where);
    }
}
