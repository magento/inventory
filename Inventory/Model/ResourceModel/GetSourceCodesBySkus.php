<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Retrieve a list of source codes by a selection of SKUs
 */
class GetSourceCodesBySkus
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
     * Retrieves distinct source codes for given SKUs by querying the `inventory_source_item` database table.
     *
     * @param array $skus
     * @return string[]
     */
    public function execute(array $skus): array
    {
        $tableName = $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);
        $connection = $this->resourceConnection->getConnection();

        $qry = $connection
            ->select()
            ->distinct()
            ->from($tableName, 'source_code')
            ->where('sku IN (?)', $skus);

        return $connection->fetchCol($qry);
    }
}
