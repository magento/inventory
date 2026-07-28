<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * Determines if products with the given SKUs are assigned to the specified stock ID.
 */
interface AreProductsAssignedToStockInterface
{
    /**
     * Determines if products with the given SKUs are assigned to the specified stock ID.
     *
     * @param string[] $skus
     * @param int $stockId
     * @return array<string, bool> An associative array where the keys are SKUs and the values boolean indicating
     * whether the product with the given SKU is assigned to the specified stock ID.
     */
    public function execute(array $skus, int $stockId): array;
}
