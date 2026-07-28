<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Returns aggregated quantity of a product across all active sources in the provided stock
 */
interface GetProductAvailableQtyInterface
{
    /**
     * Get available quantity for given SKU and Stock
     *
     * Returns NULL if there are no sources linked to the stock with the provided SKU
     * or all sources linked to the stock with the provided SKU are disabled.
     *
     * @param string $sku
     * @param int $stockId
     * @return float|null
     */
    public function execute(string $sku, int $stockId): ?float;
}
