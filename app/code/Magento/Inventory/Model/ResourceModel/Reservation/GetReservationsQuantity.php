<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\Reservation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\GetReservationsQuantityInterface;
use Magento\Inventory\Setup\Operation\CreateReservationTable;
use Magento\InventoryApi\Api\Data\ReservationInterface;

/**
 * @inheritdoc
 */
class GetReservationsQuantity implements GetReservationsQuantityInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName(CreateReservationTable::TABLE_NAME_RESERVATION);

        $select = $connection->select()
            ->from($reservationTable, [ReservationInterface::QUANTITY => 'SUM(' . ReservationInterface::QUANTITY . ')'])
            ->where(ReservationInterface::SKU . ' = ?', $sku)
            ->where(ReservationInterface::STOCK_ID . ' = ?', $stockId)
            ->limit(1);

        try {
            $reservationQty = $connection->fetchOne($select);
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'Product with sku "%sku" is not assigned to stock with id "%stock"',
                ['sku' => $sku, 'stock' => $stockId]
            ), $e);
        }

        if (false === $reservationQty) {
            $reservationQty = 0;
        }
        return (float)$reservationQty;
    }
}
