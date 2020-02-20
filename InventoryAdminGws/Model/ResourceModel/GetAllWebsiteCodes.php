<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get all available website codes resource model.
 */
class GetAllWebsiteCodes
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
     * Get all available website codes.
     *
     * @return array
     */
    public function execute(): array
    {
        $adapter = $this->connection->getConnection();
        $tableName = $this->connection->getTableName('store_website');
        $sql = $adapter->select()->from($tableName, 'code');

        return $adapter->fetchCol($sql);
    }
}
