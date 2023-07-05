<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\LegacyStockItem;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class CacheStorage implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $cachedItems = [];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->cachedItems = [];
    }

    /**
     * Save item to cache
     *
     * @param string $sku
     * @param StockItemInterface $item
     */
    public function set(string $sku, StockItemInterface $item): void
    {
        $this->cachedItems[$sku] = $item;
    }

    /**
     * Get item from cache
     *
     * @param string $sku
     * @return StockItemInterface
     */
    public function get(string $sku): ?StockItemInterface
    {
        return $this->cachedItems[$sku] ?? null;
    }

    /**
     * Delete item from cache
     *
     * @param string $sku
     */
    public function delete(string $sku): void
    {
        unset($this->cachedItems[$sku]);
    }
}
