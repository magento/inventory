<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\GetStockItemData;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class CacheStorage implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $cachedItemData = [[]];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->cachedItemData = [[]];
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
        $this->cachedItemData[$stockId][$sku] = $stockItemData;
    }

    /**
     * Get item from cache
     *
     * @param int $stockId
     * @param string $sku
     * @return array
     */
    public function get(int $stockId, string $sku): ?array
    {
        return $this->cachedItemData[$stockId][$sku] ?? null;
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
