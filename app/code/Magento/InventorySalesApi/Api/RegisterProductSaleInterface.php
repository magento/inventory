<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventorySalesApi\Api;

use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Interface RegisterProductSaleInterface
 * @api
 */
interface RegisterProductSaleInterface
{
    /**
     * Subtract product qtys from stock.
     * Return array of items that require full save
     *
     * @param float[] $items
     * @param int      $websiteId
     *
     * @return StockItemInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($items, $websiteId = null);
}
