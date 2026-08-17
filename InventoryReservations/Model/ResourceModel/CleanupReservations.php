<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;

/**
 * @inheritdoc
 */
class CleanupReservations implements CleanupReservationsInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var int
     */
    private $groupConcatMaxLen;

    /**
     * @param ResourceConnection $resource
     * @param int $groupConcatMaxLen
     * @param int $orphanLifetime
     */
    public function __construct(
        ResourceConnection $resource,
        int $groupConcatMaxLen,
        private readonly int $orphanLifetime = 3600,
    ) {
        $this->resource = $resource;
        $this->groupConcatMaxLen = $groupConcatMaxLen;
    }

    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_reservation');

        $reservationIds = array_unique(
            array_merge(
                $this->getReservationIdsByField('object_id'),
                $this->getReservationIdsByField('object_increment_id'),
                $this->getOrphanedReservationIds(),
            )
        );

        $condition = [ReservationInterface::RESERVATION_ID . ' IN (?)' => $reservationIds];
        $connection->delete($reservationTable, $condition);
    }

    /**
     * Returns reservation ids by specified field.
     *
     * @param string $field
     * @return array
     */
    private function getReservationIdsByField(string $field) : array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_reservation');
        $select = $connection->select()
            ->from(
                $reservationTable,
                ['GROUP_CONCAT(' . ReservationInterface::RESERVATION_ID . ')']
            )
            ->group("JSON_EXTRACT(metadata, '$.$field')", "JSON_EXTRACT(metadata, '$.object_type')")
            ->having('SUM(' . ReservationInterface::QUANTITY . ') = 0');
        $connection->query('SET group_concat_max_len = ' . $this->groupConcatMaxLen);
        return $connection->fetchCol($select);
    }

    /**
     * Returns reservation ids which are not linked to any order.
     *
     * @return array
     */
    private function getOrphanedReservationIds(): array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_reservation');
        $select = $connection->select()
            ->from(['ir' => $reservationTable], [ReservationInterface::RESERVATION_ID])
            ->joinLeft(
                ['so' => $this->resource->getTableName('sales_order')],
                'JSON_VALUE(ir.metadata, "$.object_increment_id") = so.increment_id',
                []
            )
            ->where('JSON_VALUE(ir.metadata, "$.event_timestamp") < UNIX_TIMESTAMP() - ' . $this->orphanLifetime)
            ->where('so.entity_id IS NULL');
        return $connection->fetchCol($select);
    }
}
