<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
use Magento\Inventory\Model\ResourceModel\IsProductAssignedToStock;
use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;

class IsProductAssignedToStockCache implements IsProductAssignedToStockInterface
{
    /**
     * @param IsProductAssignedToStock $isProductAssignedToStock
     * @param CacheStorage $isProductAssignedToStockCacheStorage
     * @param bool $isReadonly
     */
    public function __construct(
        private readonly IsProductAssignedToStock $isProductAssignedToStock,
        private readonly CacheStorage $isProductAssignedToStockCacheStorage,
        private readonly bool $isReadonly = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        if (!$this->isProductAssignedToStockCacheStorage->has($sku, $stockId)) {
            $value = $this->isProductAssignedToStock->execute($sku, $stockId);
            if (!$this->isReadonly) {
                $this->isProductAssignedToStockCacheStorage->set($sku, $stockId, $value);
            }
        } else {
            $value = $this->isProductAssignedToStockCacheStorage->get($sku, $stockId);
        }

        return $value;
    }
}
