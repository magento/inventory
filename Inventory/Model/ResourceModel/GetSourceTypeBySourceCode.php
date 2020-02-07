<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Retrieve a source type by a source code
 */
class GetSourceTypeBySourceCode
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
     * @param string $sourceCode
     * @return string
     */
    public function execute(string $sourceCode): string
    {
        $tableName = $this->resourceConnection->getTableName(SourceTypeLink::TABLE_NAME_SOURCE_TYPE_LINK);
        $connection = $this->resourceConnection->getConnection();

        $qry = $connection
            ->select()
            ->from($tableName, 'type_code')
            ->where('source_code = ?', $sourceCode);

        return $connection->fetchOne($qry);
    }
}
