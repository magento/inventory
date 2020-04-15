<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Retrieve a name of source type by a source type
 */
class GetSourceTypeNameBySourceType
{
    private const TABLE_NAME_SOURCE_TYPE = "inventory_source_type";

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
     * @param string $sourceCode
     * @return string
     */
    public function execute(string $sourceCode): string
    {
        $tableName = $this->resourceConnection->getTableName(self::TABLE_NAME_SOURCE_TYPE);
        $connection = $this->resourceConnection->getConnection();

        $qry = $connection
            ->select()
            ->from($tableName)
            ->columns('name')
            ->where('type_code = ?', $sourceCode);

        return $connection->fetchOne($qry);
    }
}
