<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model\ResourceModel\Reservation;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ReservationBuilderInterface;
use Magento\Inventory\Model\ResourceModel\Reservation as ReservationResourceModel;
use Magento\InventoryApi\Api\Data\ReservationInterface;


class GetReservationsForProduct
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    /**
     * GetReservationsForProduct constructor
     *
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource,
        ReservationBuilderInterface $reservationBuilder
    ) {
        $this->resource = $resource;
        $this->reservationBuilder = $reservationBuilder;
    }

    /**
     * Retrieve Reservations for given SKU in a given Stock
     *
     * @param string $sku
     * @param int $stockId
     * @return ReservationInterface[]
     */
    public function execute(string $sku, int $stockId): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $connection->getTableName(ReservationResourceModel::TABLE_NAME_RESERVATION);

        $select = $connection
            ->select()
            ->from($tableName, '*')
            ->where(ReservationInterface::SKU . ' = ?', $sku)
            ->where(ReservationInterface::STOCK_ID . ' = ?', $stockId);

        $reservations = [];
        foreach ($connection->fetchAll($select) as $row) {
            $reservations[] = $this->reservationBuilder
                ->setReservationId($row[ReservationInterface::RESERVATION_ID])
                ->setStockId($row[ReservationInterface::STOCK_ID])
                ->setSku($row[ReservationInterface::SKU])
                ->setQuantity($row[ReservationInterface::QUANTITY])
                ->setMetadata($row[ReservationInterface::METADATA])
                ->build();
        }

        return $reservations;
    }
}
