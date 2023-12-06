<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\GetStockItemData;

class IsProductAssignedToStockCacheStorage
{
    /**
     * @var array
     */
    private $cachedItemData = [[]];

    /**
     * Save item to cache
     *
     * @param int $stockId
     * @param string $sku
     * @param bool $skuToStockIdAssignment
     */
    public function set(int $stockId, string $sku, bool $skuToStockIdAssignment): void
    {
        $this->cachedItemData[$stockId][$sku] = $skuToStockIdAssignment;
    }

    /**
     * Get item from cache
     *
     * @param int $stockId
     * @param string $sku
     * @return bool
     */
    public function isProductAssigned(int $stockId, string $sku): bool
    {
        return $this->cachedItemData[$stockId][$sku] ?? false;
    }

    /**
     * Delete item from cache
     *
     * @param int $stockId
     * @param string $sku
     */
    public function delete(int $stockId, string $sku): void
    {
        unset($this->cachedItemData[$stockId][$sku]);
    }
}
