<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model\ResourceModel\Reservation;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\Reservation as ReservationResourceModel;
use Magento\InventoryApi\Api\Data\ReservationInterface;

class SaveMultiple
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * SaveMultiple constructor
     *
     * @param ResourceConnection $connection
     */
    public function __construct(
        ResourceConnection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @param ReservationInterface[] $reservations
     * @return void
     */
    public function execute(array $reservations)
    {
        $connection = $this->connection->getConnection();
        $tableName = $connection->getTableName(ReservationResourceModel::TABLE_NAME_RESERVATION);

        $columns = [
            ReservationInterface::RESERVATION_ID,
            ReservationInterface::STOCK_ID,
            ReservationInterface::SKU,
            ReservationInterface::QUANTITY,
            ReservationInterface::STATUS,
        ];

        $data = [];
        /** @var ReservationInterface $reservation */
        foreach ($reservations as $reservation) {
            $data[] = [
                $reservation->getReservationId(),
                $reservation->getStockId(),
                $reservation->getSku(),
                $reservation->getQuantity(),
                $reservation->getStatus(),
            ];
        }
        $connection->insertArray($tableName, $columns, $data);
    }
}
