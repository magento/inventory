<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
use Magento\Inventory\Model\ResourceModel\AreProductsAssignedToStock;
use Magento\InventoryApi\Model\AreProductsAssignedToStockInterface;
use Magento\InventoryApi\Model\CacheInterface;

class AreProductsAssignedToStockCache implements AreProductsAssignedToStockInterface, CacheInterface
{
    /**
     * @param AreProductsAssignedToStock $areProductsAssignedToStock
     * @param CacheStorage $isProductAssignedToStockCacheStorage
     */
    public function __construct(
        private readonly AreProductsAssignedToStock $areProductsAssignedToStock,
        private readonly CacheStorage $isProductAssignedToStockCacheStorage
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus, int $stockId): array
    {
        $skusToLoad = [];
        $result = [];
        foreach ($skus as $sku) {
            if ($this->isProductAssignedToStockCacheStorage->has((string) $sku, $stockId)) {
                $result[$sku] = $this->isProductAssignedToStockCacheStorage->get((string) $sku, $stockId);
            } else {
                $skusToLoad[] = $sku;
            }
        }
        if (!empty($skusToLoad)) {
            foreach ($this->areProductsAssignedToStock->execute($skusToLoad, $stockId) as $sku => $value) {
                $result[$sku] = $value;
                $this->isProductAssignedToStockCacheStorage->set((string) $sku, $stockId, $value);
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        $this->execute($skus, $stockId);
    }

    /**
     * @inheritdoc
     */
    public function clean(array $skus, ?int $stockId): void
    {
        foreach ($skus as $sku) {
            $this->isProductAssignedToStockCacheStorage->delete((string)$sku, (int)$stockId);
        }
    }
}
