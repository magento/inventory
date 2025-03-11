<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\Cache;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Cache storage for SKU/type pairs
 */
class ProductTypesBySkusStorage implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->clean();
    }

    /**
     * Get type by SKU
     *
     * @param string $sku
     * @return string|null
     */
    public function get(string $sku): ?string
    {
        return $this->storage[$this->normalizeSku($sku)] ?? null;
    }

    /**
     * Save SKU/type pair into cache
     *
     * @param string $sku
     * @param string $type
     */
    public function set(string $sku, string $type): void
    {
        $this->storage[$this->normalizeSku($sku)] = $type;
    }

    /**
     * Invalidate cache for provided sku
     *
     * @param string $sku
     */
    public function delete(string $sku): void
    {
        unset($this->storage[$this->normalizeSku($sku)]);
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
