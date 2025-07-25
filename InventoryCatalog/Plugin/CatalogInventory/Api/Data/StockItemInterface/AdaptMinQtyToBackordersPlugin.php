<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\Data\StockItemInterface;

use Magento\CatalogInventory\Api\Data\StockItemInterface;

class AdaptMinQtyToBackordersPlugin
{
    /**
     * Adapts the minimum quantity for backorders by ensuring it returns 0 or the result based on backorder status.
     *
     * @param StockItemInterface $subject
     * @param float $result
     * @return int
     */
    public function afterGetMinQty(StockItemInterface $subject, float $result)
    {
        if ($subject->getBackorders()) {
            return $result >= 0 ? 0 : $result;
        }

        return $result > 0 ? $result : 0;
    }
}
