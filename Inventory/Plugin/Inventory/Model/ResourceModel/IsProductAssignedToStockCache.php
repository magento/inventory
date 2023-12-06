<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin\Inventory\Model\ResourceModel;

use Magento\Inventory\Model\GetStockItemData\IsProductAssignedToStockCacheStorage;
use Magento\Inventory\Model\ResourceModel\IsProductAssignedToStock;

/**
 * Caching plugin for IsProductAssignedToStock service.
 */
class IsProductAssignedToStockCache
{
    /**
     * @var $cacheStorage
     */
    private IsProductAssignedToStockCacheStorage $cacheStorage;

    /**
     * @param IsProductAssignedToStockCacheStorage $cacheStorage
     */
    public function __construct(
        IsProductAssignedToStockCacheStorage $cacheStorage
    ) {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Cache service result to avoid multiple database calls for same item
     *
     * @param IsProductAssignedToStock $subject
     * @param callable $proceed
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsProductAssignedToStock $subject, callable $proceed, string $sku, int $stockId): bool
    {
        if ($this->cacheStorage->isProductAssigned($stockId, $sku)) {
            return true;
        }

        $isProductAssignableToStockCache = $proceed($sku, $stockId);

        /* Add to cache a new item */
        if ($isProductAssignableToStockCache) {
            $this->cacheStorage->set($stockId, $sku, $isProductAssignableToStockCache);
        }

        return $isProductAssignableToStockCache;
    }
}
