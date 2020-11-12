<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\Cache;

/**
 * Cache storage for ID/SKU pairs
 */
class ProductSkusByIdsStorage
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * Get SKU by ID
     *
     * @param int $id
     * @return string|null
     */
    public function get(int $id): ?string
    {
        return $this->storage[$id] ?? null;
    }

    /**
     * Saves ID/SKU pair into cache
     *
     * @param int $id
     * @param string $sku
     */
    public function set(int $id, string $sku): void
    {
        $this->storage[$id] = $sku;
    }

    /**
     * Clean storage
     *
     * @return void
     */
    public function clean()
    {
        $this->storage = [];
    }
}
