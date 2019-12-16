<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;

/**
 * Update source items sku.
 */
class UpdateSourceItemsBySku
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
     * Replace source items 'sku' value with new one.
     *
     * @param string $origSku
     * @param string $sku
     */
    public function execute(string $origSku, string $sku): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);
        $bind = ['sku' => (string)$sku];
        $where = ['sku = ?' => (string)$origSku];
        $connection->update($table, $bind, $where);
    }
}
