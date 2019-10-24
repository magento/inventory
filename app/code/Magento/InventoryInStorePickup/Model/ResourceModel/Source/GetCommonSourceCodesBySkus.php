<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Retrieve common source codes by product skus.
 */
class GetCommonSourceCodesBySkus
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
     * Select all common source by skus.
     *
     * @param array $skus
     * @return array
     */
    public function execute(array $skus): array
    {
        $adapter = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_source_item');
        $sql = $adapter->select()
            ->from($table, SourceItemInterface::SOURCE_CODE)
            ->where(SourceItemInterface::SKU . ' IN (?)', $skus)
            ->group(SourceItemInterface::SOURCE_CODE)
            ->having('COUNT(' . SourceItemInterface::SKU . ') = ?', count($skus));

        return $adapter->fetchCol($sql);
    }
}
