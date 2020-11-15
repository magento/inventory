<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\Cache;

use Magento\CatalogInventory\Api\Data\StockStatusInterface;

/**
 * Cache storage for legacy stock status
 */
class LegacyStockStatusStorage
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * Load stock status from cache
     *
     * @param int $productId
     * @param int $scopeId
     * @return StockStatusInterface
     */
    public function get(int $productId, int $scopeId): ?StockStatusInterface
    {
        return $this->storage[$productId][$scopeId] ?? null;
    }

    /**
     * Save stock status into cache
     *
     * @param int $productId
     * @param StockStatusInterface $value
     * @param int $scopeId
     * @return void
     */
    public function set(int $productId, StockStatusInterface $value, int $scopeId): void
    {
        $this->storage[$productId][$scopeId] = $value;
    }

    /**
     * Clean cache
     *
     * @return void
     */
    public function clean()
    {
        $this->storage = [];
    }
}
