<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\ConditionProcessor\CustomConditionInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\ResourceConnection;

/**
 * Based on Magento\Framework\Api\Filter builds condition
 * that can be applied to Catalog\Model\ResourceModel\Product\Collection
 * to filter products by quantity_and_stock_status
 */
class AttributeQuantityAndStock implements CustomConditionInterface
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
     * Builds condition to filter product collection by stock
     *
     * @param Filter $filter
     * @return string
     */
    public function build(Filter $filter): string
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                ['is' => 'inventory_stock'],
                ['stock_id', 'name']
            );
        $stocks = $this->resourceConnection->getConnection()->fetchAll($select);
        $total = count($stocks);
        $whereSql =  '';
        $i = 1;
        $orCondition = ' OR ';
        $quantitySelect = $this->resourceConnection->getConnection()->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpe.entity_id'
            );
        foreach ($stocks as $stock) {
            if ($total == $i) {
                $orCondition = '';
            }
            $stockClause = ['stock_'.$stock['stock_id'] =>
                $this->resourceConnection->getTableName('inventory_stock_'.$stock['stock_id'])];

            $quantitySelect->joinInner(
                $stockClause,
                'stock_'.$stock['stock_id'].'.sku=cpe.sku',
                []
            );
            $whereSql .= 'stock_'.$stock['stock_id'].'.is_salable ='. $filter->getValue() . $orCondition;
            $i++;
        }
        $quantitySelect->where($whereSql, $filter->getValue());
        $selectCondition = [
            $this->mapConditionType($filter->getConditionType()) => $quantitySelect
        ];
        return $this->resourceConnection->getConnection()
            ->prepareSqlCondition(Collection::MAIN_TABLE_ALIAS . '.entity_id', $selectCondition);
    }

    /**
     * Map equal and not equal conditions to in and not in
     *
     * @param string $conditionType
     * @return string
     */
    private function mapConditionType(string $conditionType): string
    {
        $ninConditions = ['neq'];
        return in_array($conditionType, $ninConditions, true) ? 'nin' : 'in';
    }
}
