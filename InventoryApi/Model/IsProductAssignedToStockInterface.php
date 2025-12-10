<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * @api
 */
interface IsProductAssignedToStockInterface
{
    /**
     * Determines if a product with the given SKU is assigned to the specified stock ID and returns a boolean result.
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool;
}
