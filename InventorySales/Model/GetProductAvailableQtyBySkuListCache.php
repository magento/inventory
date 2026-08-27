<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventorySales\Model\GetProductAvailableQty\CacheStorage;
use Magento\InventorySales\Model\ResourceModel\GetProductAvailableQtyBySkuList;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyBySkuListInterface;

class GetProductAvailableQtyBySkuListCache implements GetProductAvailableQtyBySkuListInterface, CacheInterface
{
    /**
     * @param GetProductAvailableQtyBySkuList $getProductAvailableQtyBySkuList
     * @param CacheStorage $getProductAvailableQtyCacheStorage
     */
    public function __construct(
        private readonly GetProductAvailableQtyBySkuList $getProductAvailableQtyBySkuList,
        private readonly CacheStorage $getProductAvailableQtyCacheStorage
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
            if ($this->getProductAvailableQtyCacheStorage->has((string) $sku, $stockId)) {
                $result[$sku] = $this->getProductAvailableQtyCacheStorage->get((string) $sku, $stockId);
            } else {
                $skusToLoad[] = $sku;
            }
        }
        if (!empty($skusToLoad)) {
            foreach ($this->getProductAvailableQtyBySkuList->execute($skusToLoad, $stockId) as $sku => $value) {
                $result[$sku] = $value;
                $this->getProductAvailableQtyCacheStorage->set((string) $sku, $stockId, $value);
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
            $this->getProductAvailableQtyCacheStorage->delete((string)$sku, (int)$stockId);
        }
    }
}
