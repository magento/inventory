<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Update source item sku resource.
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
     * Update source items for given sku.
     *
     * @param string $origSku
     * @param string $newSku
     * @return void
     */
    public function execute(string $origSku, string $newSku): void
    {
        $adapter = $this->connection->getConnection();
        $sourceItemsTableName = $this->connection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);
        $adapter->update(
            $sourceItemsTableName,
            [SourceItemInterface::SKU => $newSku],
            [SourceItemInterface::SKU . '=?' => $origSku]
        );
    }
}
