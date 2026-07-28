<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\GetStockItemData;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class CacheStorage implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $cachedItemData = [];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->cachedItemData = [];
    }

    /**
     * Save item to cache
     *
     * @param int $stockId
     * @param string $sku
     * @param array $stockItemData
     */
    public function set(int $stockId, string $sku, array $stockItemData): void
    {
        $this->cachedItemData[$sku][$stockId] = $stockItemData;
    }

    /**
     * Get item from cache
     *
     * @param int $stockId
     * @param string $sku
     * @return array|null
     */
    public function get(int $stockId, string $sku): ?array
    {
        return $this->cachedItemData[$sku][$stockId] ?? null;
    }

    /**
     * Delete item from cache
     *
     * @param string $sku
     * @param int|null $stockId
     */
    public function delete(string $sku, ?int $stockId): void
    {
        if ($stockId === null) {
            unset($this->cachedItemData[$sku]);
        } else {
            unset($this->cachedItemData[$sku][$stockId]);
        }
    }
}
