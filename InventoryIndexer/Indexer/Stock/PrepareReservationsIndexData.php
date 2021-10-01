<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;

class PrepareReservationsIndexData
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ReservationsIndexTable
     */
    private $reservationsIndexTable;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ReservationsIndexTable $reservationsIndexTable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ReservationsIndexTable $reservationsIndexTable
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->reservationsIndexTable = $reservationsIndexTable;
    }

    /**
     * Prepare reservation index data.
     *
     * @param int $stockId
     * @return void
     */
    public function execute(int $stockId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $reservationsData = $connection->select();
        $reservationsData->from(
            ['reservations' => $this->resourceConnection->getTableName('inventory_reservation')],
            [
                'sku',
                'reservation_qty' => 'SUM(reservations.quantity)'
            ]
        );
        $reservationsData->where('stock_id = ?', $stockId);
        $reservationsData->group(['sku', 'stock_id']);

        $insertFromSelect = $connection->insertFromSelect(
            $reservationsData,
            $this->reservationsIndexTable->getTableName($stockId)
        );
        $connection->query($insertFromSelect);
    }
}
