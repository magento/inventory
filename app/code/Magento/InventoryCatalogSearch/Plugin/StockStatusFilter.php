<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin;

use Magento\Framework\DB\Select;
use Magento\CatalogSearch\Model\Search\FilterMapper\StockStatusFilter as OriginStockStatusFilter;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;

/**
 * @inheritdoc
 */
class StockStatusFilter
{
    /**
     * Defines strategy of how filter should be applied
     *
     * Stock status filter will be applied only on parent products
     * (e.g. only for configurable products, without options)
     */
    const FILTER_JUST_ENTITY = 'general_filter';

    /**
     * Defines strategy of how filter should be applied
     *
     * Stock status filter will be applied on parent products with its child
     * (e.g. for configurable products and options)
     */
    const FILTER_ENTITY_AND_SUB_PRODUCTS = 'filter_with_sub_products';

    /**
     * @var ConditionManager
     */
    private $conditionManager;

    /**
     * StockStatusFilter constructor.
     * @param ConditionManager $conditionManager
     */
    public function __construct(
        ConditionManager $conditionManager
    ) {
        $this->conditionManager = $conditionManager;
    }

    /**
     * @param OriginStockStatusFilter $subject
     * @param callable $proceed
     * @param Select $select
     * @param array $stockValues
     * @param string $type
     * @param bool $showOutOfStockFlag
     * @return Select
     */
    public function arroundApply(
        OriginStockStatusFilter $subject,
        callable $proceed,
        Select $select,
        $stockValues,
        $type,
        $showOutOfStockFlag
    ) {
        if ($type !== self::FILTER_JUST_ENTITY && $type !== self::FILTER_ENTITY_AND_SUB_PRODUCTS) {
            throw new \InvalidArgumentException(sprintf('Invalid filter type: %s', $type));
        }

        $select = clone $select;
        $mainTableAlias = $this->extractTableAliasFromSelect($select);

        $this->addProductEntityJoin($select, $mainTableAlias);
        $this->addInventoryStockJoin($select, $showOutOfStockFlag);

        return $select;

    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     */
    private function addProductEntityJoin(Select $select, $mainTableAlias)
    {
        $select->joinInner(
            ['product' => 'catalog_product_entity'],
            sprintf('product.entity_id = %s.entity_id', $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     */
    private function addInventoryStockJoin(Select $select, $showOutOfStockFlag)
    {
        $select->joinInner(
            ['stock_index' => 'inventory_stock_stock_2'],
            'stock_index.sku = product.sku',
            []
        );

        if ($showOutOfStockFlag === false) {
            $select->where($this->conditionManager->generateCondition(
                'stock_index.quantity',
                '>',
                0
            ));
        }
    }
}
