<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * Defines methods to warm up and clean the cache for inventory data by SKU list and stock ID.
 *
 * This interface specifies the contract for caching inventory data, enabling implementations to manage
 * specific inventory data.
 *
 * Implementations can improve performance by preloading data before processing individual SKU.
 * The clean method enables clearing the cache, ensuring that updated inventory data is fetched when necessary.
 */
interface CacheInterface
{
    /**
     * Bulk loads inventory data for the given SKU list and stock ID into the cache.
     *
     * @param array $skus List of SKUs to warm the cache for.
     * @param int $stockId Stock ID to warm the cache for.
     * @return void
     */
    public function warmup(array $skus, int $stockId): void;
    
    /**
     * Cleans the cache for the given SKU list and stock ID.
     *
     * @param array $skus List of SKUs to clean the cache for.
     * @param int|null $stockId Stock ID to clean the cache for, or null to clean for all stocks.
     * @return void
     */
    public function clean(array $skus, ?int $stockId): void;
}
