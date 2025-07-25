<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model\ResourceModel\ShipmentSource;

use Magento\Framework\App\ResourceConnection;

/**
 * Deletes shipment source records from the `inventory_shipment_source` table based on the provided shipment ID.
 */
class DeleteShipmentSource
{
    /**
     * Constant for fields in data array
     */
    public const SHIPMENT_ID = 'shipment_id';

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
     * Deletes shipment source records from the `inventory_shipment_source` table based on the provided shipment ID.
     *
     * @param int $shipmentId
     * @return void
     */
    public function execute(int $shipmentId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_shipment_source');

        $connection->delete($tableName, [
            self::SHIPMENT_ID . ' = ?' => $shipmentId,
        ]);
    }
}
