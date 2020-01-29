<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Update source item sku resource.
 */
class UpdateSourceItemsSku
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
     * Update source items for given sku.
     *
     * @param string $origSku
     * @param string $newSku
     * @return void
     */
    public function execute(string $origSku, string $newSku): void
    {
        $adapter = $this->connection->getConnection();
        $sourceItemsTableName = $this->connection->getTableName('inventory_source_item');
        $adapter->update(
            $sourceItemsTableName,
            [SourceItemInterface::SKU => $newSku],
            [SourceItemInterface::SKU . '=?' => $origSku]
        );
    }
}
