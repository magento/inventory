<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Delete reservation by product skus.
 */
class DeleteReservationsBySkus
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
     * Delete reservation for given product skus.
     *
     * @param array $skus
     * @return void
     * @throws \Exception
     */
    public function execute(array $skus): void
    {
        $adapter = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_reservation');
        $adapter->delete($table, $adapter->quoteInto('sku IN (?)', $skus));
    }
}
