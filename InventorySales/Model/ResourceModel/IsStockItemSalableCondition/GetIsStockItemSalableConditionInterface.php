<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;

/**
 * Responsible for building is_salable conditions foe stock item
 *
 * @api
 */
interface GetIsStockItemSalableConditionInterface
{
    /**
     * Builds and returns a condition string to determine if a stock item is salable based on backorder settings.
     *
     * @param Select $select
     * @return string
     */
    public function execute(Select $select): string;
}
