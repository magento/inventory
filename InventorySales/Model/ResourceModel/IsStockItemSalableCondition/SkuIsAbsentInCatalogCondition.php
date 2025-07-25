<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;

/**
 * Still build Stock Item index even when there is no corresponding SKU in catalog_product_entity table
 */
class SkuIsAbsentInCatalogCondition implements GetIsStockItemSalableConditionInterface
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Select $select): string
    {
        return 'product.sku IS NULL';
    }
}
