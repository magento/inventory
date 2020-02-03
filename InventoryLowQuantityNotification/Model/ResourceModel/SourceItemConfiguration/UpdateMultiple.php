<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Update source items configuration sku resource.
 */
class UpdateMultiple
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
     * Update source item configuration for given skus.
     *
     * @param string $origSku
     * @param string $newSku
     * @return void
     */
    public function execute(string $origSku, string $newSku): void
    {
        $adapter = $this->connection->getConnection();
        $sourceItemsTableName = $this->connection->getTableName('inventory_low_stock_notification_configuration');
        $adapter->update(
            $sourceItemsTableName,
            [SourceItemInterface::SKU => $newSku],
            [SourceItemInterface::SKU . '=?' => $origSku]
        );
    }
}
