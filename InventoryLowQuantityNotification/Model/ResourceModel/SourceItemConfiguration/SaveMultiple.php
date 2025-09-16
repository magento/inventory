<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;

/**
 * Implementation of Source Item Configuration save multiple operation for specific db layer
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
     * Inserts or updates multiple source item configurations in the database for low stock notifications efficiently.
     *
     * @param SourceItemConfigurationInterface[] $sourceItemConfigurations
     * @return void
     */
    public function execute(array $sourceItemConfigurations)
    {
        if (!count($sourceItemConfigurations)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection
            ->getTableName('inventory_low_stock_notification_configuration');

        $columnsSql = $this->buildColumnsSqlPart([
            SourceItemConfigurationInterface::SOURCE_CODE,
            SourceItemConfigurationInterface::SKU,
            SourceItemConfigurationInterface::INVENTORY_NOTIFY_QTY
        ]);

        $valuesSql = $this->buildValuesSqlPart($sourceItemConfigurations);
        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            SourceItemConfigurationInterface::INVENTORY_NOTIFY_QTY
        ]);
        $bind = $this->getSqlBindData($sourceItemConfigurations);

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES %s %s',
            $tableName,
            $columnsSql,
            $valuesSql,
            $onDuplicateSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Quotes column names for safe SQL execution and concatenates them into a comma-separated string.
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
     * Generates a SQL `VALUES` clause for bulk inserts by repeating placeholders based on the input array size.
     *
     * @param SourceItemConfigurationInterface[] $sourceItemConfigurations
     * @return string
     */
    private function buildValuesSqlPart(array $sourceItemConfigurations): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?), ', count($sourceItemConfigurations)), ', ');
        return $sql;
    }

    /**
     * Generates SQL bind data for source item configurations by merging source code, SKU, and notify stock quantity.
     *
     * @param SourceItemConfigurationInterface[] $sourceItemConfigurations
     * @return array
     */
    private function getSqlBindData(array $sourceItemConfigurations): array
    {
        $bind = [];
        foreach ($sourceItemConfigurations as $sourceItemConfiguration) {
            //phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $bind = array_merge($bind, [
                $sourceItemConfiguration->getSourceCode(),
                $sourceItemConfiguration->getSku(),
                $sourceItemConfiguration->getNotifyStockQty()
            ]);
        }
        return $bind;
    }

    /**
     * Builds the SQL `ON DUPLICATE KEY UPDATE` clause for fields, quoting identifiers for safe SQL execution.
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
