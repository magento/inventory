<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Load reservations from database.
 */
class GetReservationsList
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
     * Load reservations from database.
     *
     * @return array
     */
    public function execute(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');

        $query = $connection
            ->select()
            ->from($tableName);
        return $connection->fetchAll($query);
    }
}
