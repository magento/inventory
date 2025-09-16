<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\Spi\StockStateProvider;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;

class AdaptVerifyStockToNegativeMinQtyPlugin
{
    /**
     * Validates stock availability for backorders by checking if quantity is below a negative minimum quantity.
     *
     * @param StockStateProviderInterface $subject
     * @param bool $result
     * @param StockItemInterface $stockItem
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterVerifyStock(StockStateProviderInterface $subject, bool $result, StockItemInterface $stockItem)
    {
        if ($stockItem->getBackorders() !== StockItemInterface::BACKORDERS_NO
            && $stockItem->getMinQty() < 0
            && $stockItem->getQty() < $stockItem->getMinQty()
        ) {
            return false;
        }
        return $result;
    }
}
