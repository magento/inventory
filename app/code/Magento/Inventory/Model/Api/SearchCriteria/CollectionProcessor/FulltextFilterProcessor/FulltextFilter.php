<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Api\SearchCriteria\CollectionProcessor\FulltextFilterProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\Filter;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class FulltextFilter
 */
class FulltextFilter implements CustomFilterInterface
{
    /**
     * Returns list of columns from fulltext index (doesn't support more then one FTI per table)
     *
     * @param AbstractDb $collection
     * @param string $indexTable
     * @return array
     */
    protected function getFulltextIndexColumns(AbstractDb $collection, string $indexTable): array
    {
        $indexes = $collection->getConnection()->getIndexList($indexTable);
        foreach ($indexes as $index) {
            if (strtoupper($index['INDEX_TYPE']) === 'FULLTEXT') {
                return $index['COLUMNS_LIST'];
            }
        }
        return [];
    }

    /**
     * Add table alias to columns
     *
     * @param array $columns
     * @param AbstractDb $collection
     * @param string $indexTable
     * @return array
     * @throws \Exception
     */
    protected function addTableAliasToColumns(array $columns, AbstractDb $collection, string $indexTable): array
    {
        $alias = '';
        /** @var array $fromTables */
        $fromTables = $collection->getSelect()->getPart(Select::FROM);
        foreach ($fromTables as $tableAlias => $data) {
            if ($indexTable === $data['tableName']) {
                $alias = $tableAlias;
                break;
            }
        }
        if ($alias) {
            $columns = array_map(
                function ($column) use ($alias) {
                    return '`' . $alias . '`.' . $column;
                },
                $columns
            );
        }

        return $columns;
    }

    /**
     * Returns main table name - extracted from "module/table" style and
     * validated by db adapter
     *
     * @param AbstractDb $collection
     * @return string
     * @throws \Exception
     */
    protected function getMainTable(AbstractDb $collection): string
    {
        /** @var array $fromTables */
        $fromTables = $collection->getSelect()->getPart(Select::FROM);
        foreach ($fromTables as $tableMetadata) {
            if ($tableMetadata['joinType'] === 'from') {
                return $tableMetadata['tableName'];
            }
        }

        throw new LocalizedException(__('There is no FROM clause in the query'));
    }

    /**
     * Apply fulltext filters
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool
     * @throws \Exception
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $mainTable = $this->getMainTable($collection);
        $columns = $this->getFulltextIndexColumns($collection, $mainTable);
        if (!$columns) {
            return false;
        }

        $columns = $this->addTableAliasToColumns($columns, $collection, $mainTable);
        $collection->getSelect()
            ->where(
                'MATCH(' . implode(',', $columns) . ') AGAINST(?)',
                $filter->getValue()
            );

        return true;
    }
}
