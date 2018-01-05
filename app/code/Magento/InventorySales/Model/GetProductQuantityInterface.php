<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Get Product Quantity from legacy catalog stock item
 */
interface GetProductQuantityInterface
{

    /**
     * @param StockItemInterface $legacyStockItem
     *
     * @return float|null
     */
    public function execute(StockItemInterface $legacyStockItem);
}
