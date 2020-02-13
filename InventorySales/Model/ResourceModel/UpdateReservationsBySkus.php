<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Update reservation by product sku.
 */
class UpdateReservationsBySkus
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(ResourceConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Replace reservations 'sku' value with new one.
     *
     * @param \Magento\InventorySales\Plugin\Catalog\Model\SkuDataForReservationUpdate[] $skus
     * @return void
     */
    public function execute(array $skus): void
    {
        /** @var \Magento\InventorySales\Plugin\Catalog\Model\SkuDataForReservationUpdate $sku */
        foreach ($skus as $sku) {
            $connection = $this->connection->getConnection();
            $table = $this->connection->getTableName('inventory_reservation');
            $bind = ['sku' => $sku->getNew()];
            $where = ['sku = ?' => $sku->getOld()];
            $connection->update($table, $bind, $where);
        }
    }
}
