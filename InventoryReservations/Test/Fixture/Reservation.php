<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Test\Fixture;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\InventoryReservations\Model\ResourceModel\SaveMultiple;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * <pre>
 *     $data = [
 *       'stock_id' => (int) Stock ID. Default: Default stock (1)
 *       'sku' => (string) SKU.
 *       'quantity' => (float) Quantity. Default: 1.
 *       'metadata' => (array) Metadata. Optional.
 *     ]
 *  </pre>
 */
class Reservation implements RevertibleDataFixtureInterface
{
    /**
     * @param ReservationBuilderInterface $reservationBuilder
     * @param SaveMultiple $saveMultiple
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ReservationBuilderInterface $reservationBuilder,
        private readonly SaveMultiple $saveMultiple,
        private readonly ResourceConnection $resourceConnection,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $this->reservationBuilder->setStockId($data['stock_id'] ?? 1)
            ->setSku($data['sku'] ?? '')
            ->setQuantity($data['quantity'] ?? 1);
        if (isset($data['metadata'])) {
            $metadata = json_encode($data['metadata']);
            $this->reservationBuilder->setMetadata($metadata);
        }
        $reservation = $this->reservationBuilder->build();
        $this->saveMultiple->execute([$reservation]);
        $reservationId = $this->resourceConnection->getConnection()->lastInsertId();

        return new DataObject(['reservation_id' => $reservationId]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');
        $this->resourceConnection->getConnection()
            ->delete($tableName, ['reservation_id = ?' => $data['reservation_id']]);
    }
}
