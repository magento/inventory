<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model\ResourceModel\Reservation;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ReservationFactory;
use Magento\Inventory\Model\ResourceModel\Reservation as ReservationResourceModel;
use Magento\InventoryApi\Api\Data\ReservationInterface;


class GetReservationsForProduct
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ReservationFactory
     */
    protected $reservationFactory;

    /**
     * GetReservationsForProduct constructor
     *
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource,
        DataObjectHelper $dataObjectHelper,
        ReservationFactory $reservationFactory
    ) {
        $this->resource = $resource;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->reservationFactory = $reservationFactory;
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
            $reservation = $this->reservationFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $reservation,
                $row,
                ReservationInterface::class
            );
            $reservations[] = $reservation;
        }

        return $reservations;
    }
}
