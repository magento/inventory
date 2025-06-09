<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Retrieves the website code associated with a given website ID from the store_website database table.
 */
class GetWebsiteCodeByWebsiteId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Fetches the website code for a given website ID from the store_website database table, or returns null.
     *
     * @param int $websiteId
     * @return string|null
     */
    public function execute(int $websiteId): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('store_website');
        $selectQry = $connection->select()->from($tableName, 'code')->where('website_id = ?', $websiteId);

        $result = $connection->fetchOne($selectQry);
        return (false === $result) ? null : $result;
    }
}
