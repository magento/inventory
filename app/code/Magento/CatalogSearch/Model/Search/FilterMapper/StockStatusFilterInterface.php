<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Search\FilterMapper;

use Magento\Framework\DB\Select;

/**
 * Adds filter by stock status to base select (SPI)
 */
interface StockStatusFilterInterface
{
    /**
     * Defines strategy of how filter should be applied
     *
     * Stock status filter will be applied only on parent products
     * (e.g. only for configurable products, without options)
     */
    const FILTER_JUST_ENTITY = 'general_filter';
    const FILTER_ENTITY_AND_SUB_PRODUCTS = 'filter_with_sub_products';
    const FILTER_JUST_SUB_PRODUCTS = 'filter_just_sub_products';

    /**
     * Adds filter by stock status to base select
     *
     * @param Select $select
     * @param mixed $stockValues
     * @param string $type
     * @param bool $showOutOfStockFlag
     * @return Select
     * @throws \InvalidArgumentException
     */
    public function apply(Select $select, $stockValues, $type, $showOutOfStockFlag);
}
