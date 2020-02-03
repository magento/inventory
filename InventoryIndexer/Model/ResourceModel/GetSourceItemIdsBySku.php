<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Get source items ids by product sku resource.
 */
class GetSourceItemIdsBySku
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
     * Retrieve source items ids by sku.
     *
     * @param string $sku
     * @return array
     */
    public function execute(string $sku): array
    {
        $adapter = $this->connection->getConnection();
        $sourceItemTable = $this->connection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);
        $select = $adapter->select()->from($sourceItemTable, SourceItem::ID_FIELD_NAME)
            ->where(SourceItemInterface::SKU . ' =?', $sku);

        return $adapter->fetchCol($select) ?: [];
    }
}
