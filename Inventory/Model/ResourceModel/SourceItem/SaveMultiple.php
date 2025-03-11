<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
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

        [$newItems, $existingItems] = $this->separateExistingAndNewItems($sourceItems);
        if (count($newItems)) {
            $this->insertNewItems($newItems, $connection, $tableName);
        }
        if (count($existingItems)) {
            $this->updateExistentItems($existingItems, $connection, $tableName);
        }
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

    /**
     * Separate and return new and existing Source Items by mapping provided Items with stored ones.
     *
     * @param array $sourceItems
     * @return array
     */
    private function separateExistingAndNewItems(array $sourceItems): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);

        $skus = [];
        $stock = [];
        foreach ($sourceItems as $sourceItem) {
            $skus[] = $sourceItem->getSku();
            $stock[] = $sourceItem->getSourceCode();
        }

        $storedSourceItems = $connection->fetchAll(
            $connection->select()
                ->from($tableName, ['source_item_id', 'source_code', 'sku'])
                ->where('sku IN (?)', $skus)->where('source_code IN (?)', $stock)
        );

        $exisingSourceItems = [];
        foreach ($sourceItems as $key => $sourceItem) {
            foreach ($storedSourceItems as $storedSourceItem) {
                if ($sourceItem->getSku() === $storedSourceItem['sku'] &&
                    $sourceItem->getSourceCode() === $storedSourceItem['source_code']) {
                    unset($sourceItems[$key]);
                    $exisingSourceItems[$storedSourceItem['source_item_id']] = $sourceItem;
                }
            }
        }
        return [$sourceItems, $exisingSourceItems];
    }

    /**
     * Insert new Source Items.
     *
     * @param array $sourceItems
     * @param AdapterInterface $connection
     * @param string $tableName
     * @return void
     */
    private function insertNewItems(array $sourceItems, AdapterInterface $connection, string $tableName): void
    {
        $columnsSql = $this->buildColumnsSqlPart([
            SourceItemInterface::SOURCE_CODE,
            SourceItemInterface::SKU,
            SourceItemInterface::QUANTITY,
            SourceItemInterface::STATUS
        ]);
        $valuesSql = $this->buildValuesSqlPart($sourceItems);
        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            SourceItemInterface::QUANTITY,
            SourceItemInterface::STATUS,
        ]);
        $bind = $this->getSqlBindData($sourceItems);

        $insertSql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s %s',
            $tableName,
            $columnsSql,
            $valuesSql,
            $onDuplicateSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Update existing Source Items.
     *
     * @param array $sourceItems
     * @param AdapterInterface $connection
     * @param string $tableName
     * @return void
     */
    private function updateExistentItems(array $sourceItems, AdapterInterface $connection, string $tableName): void
    {
        foreach ($sourceItems as $sourceItemId => $sourceItem) {
            $bind = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::SKU => $sourceItem->getSku(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::STATUS => $sourceItem->getStatus()
            ];

            $connection->update($tableName, $bind, ['source_item_id = ?' => $sourceItemId]);
        }
    }
}
