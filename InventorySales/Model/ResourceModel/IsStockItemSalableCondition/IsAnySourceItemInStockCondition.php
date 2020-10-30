<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check if product has source items with the in stock status
 */
class IsAnySourceItemInStockCondition implements
    GetIsStockItemSalableConditionInterface,
    RequiredSalableConditionInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Select $select): string
    {
        $isStockItemInStock = (string) $select->getConnection()
            ->getCheckSql(
                'source_item.' . SourceItemInterface::STATUS . ' = ' . SourceItemInterface::STATUS_OUT_OF_STOCK,
                0,
                1
            );
        return "SUM($isStockItemInStock) > 0";
    }
}
