<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Returns aggregated quantity of products across all active sources in the provided stock
 */
interface GetProductAvailableQtyBySkuListInterface
{
    /**
     * Get available quantity for given list of SKUs and Stock
     *
     * @param string[] $skus
     * @param int $stockId
     * @return array<string, float|null> An associative array where the keys are SKUs and the values are
     * the available quantities. If there are no sources linked to the stock with the provided SKU
     * or all sources linked to the stock with the provided SKU are disabled, the value will be NULL.
     */
    public function execute(array $skus, int $stockId): array;
}
