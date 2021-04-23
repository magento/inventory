<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\ResourceModel\Export;

use Magento\Framework\App\ResourceConnection;

/**
 * Gets array of website ids by provided array of website codes
 */
class GetWebsiteCodesByWebsiteIds
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
     * @param array $websiteIds
     * @return array
     */
    public function execute(array $websiteIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('store_website');
        $selectQry = $connection->select()->from($tableName, 'code')->where('website_id IN (?)', $websiteIds);

        $result = $connection->fetchCol($selectQry);
        return (false === $result) ? [] : $result;
    }
}
