<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\StockSourceLink;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Implementation of StockSourceLink save multiple operation for specific db layer
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
     * Multiple save StockSourceLinks
     *
     * @param StockSourceLinkInterface[] $links
     * @return void
     */
    public function execute(array $links)
    {
        if (!count($links)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(
            StockSourceLinkResourceModel::TABLE_NAME_STOCK_SOURCE_LINK
        );

        $columnsSql = $this->buildColumnsSqlPart([
            StockSourceLink::SOURCE_CODE,
            StockSourceLink::STOCK_ID,
            StockSourceLink::PRIORITY,
        ]);
        $valuesSql = $this->buildValuesSqlPart($links);
        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            StockSourceLink::PRIORITY,
        ]);
        $bind = $this->getSqlBindData($links);

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
     * Generates a comma-separated list of SQL column names by quoting identifiers for safe database queries.
     *
     * @param array $columns
     * @return string
     */
    private function buildColumnsSqlPart(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        return implode(', ', $processedColumns);
    }

    /**
     * Builds an SQL `VALUES` clause for bulk inserts by repeating placeholders based on the number of input links.
     *
     * @param StockSourceLinkInterface[] $links
     * @return string
     */
    private function buildValuesSqlPart(array $links): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?), ', count($links)), ', ');
        return $sql;
    }

    /**
     * Generates SQL bind data for bulk inserts by extracting source code, stock ID, and priority from links.
     *
     * @param StockSourceLinkInterface[] $links
     * @return array
     */
    private function getSqlBindData(array $links): array
    {
        $bind = [];
        foreach ($links as $link) {
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $bind = array_merge($bind, [
                $link->getSourceCode(),
                $link->getStockId(),
                $link->getPriority(),
            ]);
        }
        return $bind;
    }

    /**
     * Builds an SQL `ON DUPLICATE KEY UPDATE` clause by quoting field names and mapping them to their new values.
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
        return 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
    }
}
