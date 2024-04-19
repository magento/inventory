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

namespace Magento\InventoryCatalogRule\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\ConditionProcessor\CustomConditionInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogCollection;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\Stock\CollectionFactory;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

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
     * @var CollectionFactory
     */
    private $stockCollectionFactory;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $stockCollectionFactory
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionFactory $stockCollectionFactory,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->stockCollectionFactory = $stockCollectionFactory;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Builds condition to filter product collection by stock
     *
     * @param Filter $filter
     * @return string
     */
    public function build(Filter $filter): string
    {
        $collection = $this->stockCollectionFactory->create();
        $total = count($collection->getAllIds());
        $defaultStockId = $this->defaultStockProvider->getId();
        $whereSql =  '';
        $i = 1;
        $orCondition = ' OR ';
        $quantitySelect = $this->resourceConnection->getConnection()->select()
            ->from(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpe.entity_id'
            );
        foreach ($collection->getAllIds() as $stockId) {
            if ($total == $i) {
                $orCondition = '';
            }
            $stockIndexTableName = $this->stockIndexTableNameResolver->execute((int)$stockId);
            $stockClause = ['stock_'.$stockId => $stockIndexTableName];

            $quantitySelect->joinInner(
                $stockClause,
                'stock_'.$stockId.'.sku=cpe.sku',
                []
            );
            if ($stockId === $defaultStockId) {
                $stockIndexTableName = $this->resourceConnection->getTableName('cataloginventory_stock_status');
                $quantitySelect->joinInner(
                    ['child_stock_default' => $stockIndexTableName],
                    'child_stock_default.product_id = l.product_id',
                    []
                )->joinInner(
                    ['parent_stock_default' => $stockIndexTableName],
                    'parent_stock_default.product_id = le.entity_id',
                    []
                )->where(
                    'child_stock_default.stock_status = 1 OR parent_stock_default.stock_status = 0'
                );
            } else {
                $whereSql .= 'stock_' . $stockId . '.is_salable =' . $filter->getValue() . $orCondition;
            }
            $i++;
        }
        $quantitySelect->where($whereSql, $filter->getValue());
        $selectCondition = [
            $this->mapConditionType($filter->getConditionType()) => $quantitySelect
        ];
        return $this->resourceConnection->getConnection()
            ->prepareSqlCondition(CatalogCollection::MAIN_TABLE_ALIAS . '.entity_id', $selectCondition);
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
