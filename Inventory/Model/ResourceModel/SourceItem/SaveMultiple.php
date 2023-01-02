<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Implementation of SourceItem save multiple operation for specific db layer
 *
 * Save Multiple used here for performance efficient purposes over single save operation
 */
class SaveMultiple
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
     * Multiple save source items
     *
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function execute(array $sourceItems)
    {
        if (!count($sourceItems)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);

        $columnsSql = $this->buildColumnsSqlPart([
            SourceItemInterface::SOURCE_CODE,
            SourceItemInterface::SKU,
            SourceItemInterface::QUANTITY,
            SourceItemInterface::STATUS
        ]);
        $valuesSql = $this->buildValuesSqlPart($sourceItems);
        $bind = $this->getSqlBindData($sourceItems);

        /* can not use "INSERT ... ON DUPLICATE KEY UPDATE" statement against a table having more than one unique
        or primary key because it is unsafe */
        $insertSql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $tableName,
            $columnsSql,
            $valuesSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Build column sql part
     *
     * @param array $columns
     * @return string
     */
    private function buildColumnsSqlPart(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        $sql = implode(', ', $processedColumns);
        return $sql;
    }

    /**
     * Build sql query for values
     *
     * @param SourceItemInterface[] $sourceItems
     * @return string
     */
    private function buildValuesSqlPart(array $sourceItems): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?, ?), ', count($sourceItems)), ', ');
        return $sql;
    }

    /**
     * Get Sql bind data
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function getSqlBindData(array $sourceItems): array
    {
        $bind = [];
        foreach ($sourceItems as $sourceItem) {
            $bind[] = $sourceItem->getSourceCode();
            $bind[] = $sourceItem->getSku();
            $bind[] = $sourceItem->getQuantity();
            $bind[] = $sourceItem->getStatus();
        }
        return $bind;
    }

    /**
     * Build sql query for on duplicate event
     *
     * @param array $fields
     * @return string
     */
    private function buildOnDuplicateSqlPart(array $fields): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedFields = [];
        foreach ($fields as $field) {
            $processedFields[] = sprintf('%1$s = VALUES(%1$s)', $connection->quoteIdentifier($field));
        }
        $sql = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
        return $sql;
    }
}
