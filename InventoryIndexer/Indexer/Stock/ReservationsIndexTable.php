<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class ReservationsIndexTable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Create temporary index table
     *
     * @param int $stockId
     * @throws \Zend_Db_Exception
     */
    public function createTable(int $stockId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $reservationsTableName = $this->getTableName($stockId);
        $table = $connection->newTable($reservationsTableName);
        $table->addColumn(
            'sku',
            Table::TYPE_TEXT,
            64,
            [
                Table::OPTION_PRIMARY => true,
                Table::OPTION_NULLABLE => false,
            ],
            'Sku'
        );
        $table->addColumn(
            'reservation_qty',
            Table::TYPE_DECIMAL,
            null,
            [
                Table::OPTION_UNSIGNED => false,
                Table::OPTION_NULLABLE => false,
                Table::OPTION_DEFAULT => 0,
                Table::OPTION_PRECISION => 10,
                Table::OPTION_SCALE => 4,
            ],
            'Reservation Qty'
        );
        $table->addIndex(
            'index_sku_qty',
            ['sku'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $connection->dropTemporaryTable($reservationsTableName);
        $connection->createTemporaryTable($table);
    }

    /**
     * Return temporary table name.
     *
     * @param int $stockId
     * @return string
     */
    public function getTableName(int $stockId): string
    {
        return $this->resourceConnection->getTableName('reservations_temp_for_stock_' . $stockId);
    }

    /**
     * Drop temporary index table.
     *
     * @param int $stockId
     */
    public function dropTable(int $stockId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->dropTemporaryTable($this->getTableName($stockId));
    }
}
