<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Inventory\Model\IsProductAssignedToStock;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class CacheStorage implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private array $cache = [];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->clean();
    }

    /**
     * Get value from cache
     *
     * @param string $sku
     * @param int $stockId
     * @return bool|null
     * @SuppressWarnings(PHPMD.BooleanGetMethodName) Consistent with other cache storages
     */
    public function get(string $sku, int $stockId): ?bool
    {
        return $this->cache[$sku][$stockId] ?? null;
    }

    /**
     * Save value into cache
     *
     * @param string $sku
     * @param int $stockId
     * @param ?bool $value
     */
    public function set(string $sku, int $stockId, bool $value): void
    {
        $this->cache[$sku][$stockId] = $value;
    }

    /**
     * Check if cache has value for provided sku and stock id
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function has(string $sku, int $stockId): bool
    {
        return isset($this->cache[$sku]) && array_key_exists($stockId, $this->cache[$sku]);
    }

    /**
     * Invalidate cache for provided sku and stock id
     *
     * @param string $sku
     * @param int|null $stockId
     */
    public function delete(string $sku, ?int $stockId = null): void
    {
        if ($stockId === null) {
            unset($this->cache[$sku]);
        } else {
            unset($this->cache[$sku][$stockId]);
        }
    }

    /**
     * Clean cache
     */
    public function clean(): void
    {
        $this->cache = [];
    }
}
