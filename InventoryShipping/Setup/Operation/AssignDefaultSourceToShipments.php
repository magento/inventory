<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Setup\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

class AssignDefaultSourceToShipments
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
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Assigns the default source code to all shipments by inserting data into the `inventory_shipment_source` table.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function execute(ModuleDataSetupInterface $setup)
    {
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        $sourceShipmentTable = $setup->getTable('inventory_shipment_source');
        $salesShipmentTable = $setup->getTable('sales_shipment');

        $selectForInsert = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $salesShipmentTable,
                [
                    'entity_id',
                    'source_code' => new \Zend_Db_Expr('\'' .$defaultSourceCode . '\'')
                ]
            );

        $sql = $this->resourceConnection->getConnection()->insertFromSelect(
            $selectForInsert,
            $sourceShipmentTable,
            [
                self::SHIPMENT_ID,
                self::SOURCE_CODE,
            ],
            Mysql::INSERT_ON_DUPLICATE
        );
        $this->resourceConnection->getConnection()->query($sql);
    }
}
