<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model\ResourceModel\ShipmentSource;

use Magento\Framework\App\ResourceConnection;

/**
 * Saves shipment source records to the `inventory_shipment_source` table, updating the source code if it already exists
 */
class SaveShipmentSource
{
    /**
     * Constant for fields in data array
     */
    public const SHIPMENT_ID = 'shipment_id';
    public const SOURCE_CODE = 'source_code';

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
     * Saves or updates a shipment source record in the `inventory_shipment_source` table with given data.
     *
     * @param int $shipmentId
     * @param string $sourceCode
     * @return void
     */
    public function execute(int $shipmentId, string $sourceCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_shipment_source');

        $data = [
            self::SHIPMENT_ID => $shipmentId,
            self::SOURCE_CODE => $sourceCode
        ];

        $connection->insertOnDuplicate($tableName, $data, [self::SOURCE_CODE]);
    }
}
