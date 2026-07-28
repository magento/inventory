<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityBySkuListInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

class GetReservationsQuantityBySkuList implements GetReservationsQuantityBySkuListInterface
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus, int $stockId): array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from(
                $reservationTable,
                [
                    ReservationInterface::SKU,
                    ReservationInterface::QUANTITY => 'SUM(' . ReservationInterface::QUANTITY . ')'
                ]
            )
            ->where(ReservationInterface::STOCK_ID . ' = ?', $stockId)
            ->where(ReservationInterface::SKU . ' IN (?)', $skus)
            ->group([ReservationInterface::STOCK_ID, ReservationInterface::SKU]);

        $result = $connection->fetchPairs($select);
        foreach ($skus as $sku) {
            if (!isset($result[$sku])) {
                $result[$sku] = 0;
            }
        }
        return array_map(fn ($value) => (float) $value, $result);
    }
}
