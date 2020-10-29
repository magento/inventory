<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\Cache;

/**
 * Cache storage for SKU/ID pairs
 */
class ProductIdsBySkusStorage
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * Get ID by SKU
     *
     * @param string $sku
     * @return int|null
     */
    public function get(string $sku): ?int
    {
        return $this->storage[$this->normalizeSku($sku)] ?? null;
    }

    /**
     * Save SKU/ID pair into cache
     *
     * @param string $sku
     * @param int $id
     */
    public function set(string $sku, int $id): void
    {
        $this->storage[$this->normalizeSku($sku)] = $id;
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

    /**
     * Normalize SKU by converting it to lowercase.
     *
     * @param string $sku
     * @return string
     */
    private function normalizeSku(string $sku): string
    {
        return mb_convert_case($sku, MB_CASE_LOWER, 'UTF-8');
    }
}
