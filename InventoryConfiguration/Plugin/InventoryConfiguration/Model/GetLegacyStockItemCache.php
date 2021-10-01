<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

/**
 * Caching plugin for GetLegacyStockItem service.
 */
class GetLegacyStockItemCache
{
    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @param CacheStorage $cacheStorage
     */
    public function __construct(CacheStorage $cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Cache the result of service to avoid duplicate queries to the database.
     *
     * @param GetLegacyStockItem $subject
     * @param callable $proceed
     * @param string $sku
     * @return StockItemInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(GetLegacyStockItem $subject, callable $proceed, string $sku): StockItemInterface
    {
        if ($this->cacheStorage->get($sku)) {
            return $this->cacheStorage->get($sku);
        }

        /** @var StockItemInterface $item */
        $item = $proceed($sku);
        /* Avoid add to cache a new item */
        if ($item->getItemId()) {
            $this->cacheStorage->set($sku, $item);
        }

        return $item;
    }
}
