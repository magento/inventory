<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationsApi\Api\Data\ReservationInterface;
use Magento\InventoryReservations\Model\CleanupReservationsInterface;
use Magento\InventoryReservations\Setup\Operation\CreateReservationTable;
use Magento\InventoryIndexer\Indexer\SourceItem\GetGroupConcatMaxLen;

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
     * @var GetGroupConcatMaxLen
     */
    private $getGroupConcatMaxLen;

    /**
     * @param ResourceConnection $resource
     * @param int $groupConcatMaxLen
     */
    public function __construct(
        ResourceConnection $resource,
        GetGroupConcatMaxLen $getGroupConcatMaxLen
    ) {
        $this->resource = $resource;
        $this->getGroupConcatMaxLen = $getGroupConcatMaxLen;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName(CreateReservationTable::TABLE_NAME_RESERVATION);

        $select = $connection->select()
            ->from(
                $reservationTable,
                ['GROUP_CONCAT(' . ReservationInterface::RESERVATION_ID . ')']
            )
            ->group([ReservationInterface::STOCK_ID, ReservationInterface::SKU])
            ->having('SUM(' . ReservationInterface::QUANTITY . ') = 0');
        $connection->query('SET group_concat_max_len = ' . $this->getGroupConcatMaxLen->execute());
        $groupedReservationIds = implode(',', $connection->fetchCol($select));

        $condition = [ReservationInterface::RESERVATION_ID . ' IN (?)' => explode(',', $groupedReservationIds)];
        $connection->delete($reservationTable, $condition);
    }
}
