<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySales\Model\GetProductAvailableQty\CacheStorage;
use Magento\InventorySales\Model\ResourceModel\GetProductAvailableQty;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyInterface;

/**
 * Check if product has source items with the in stock status.
 */
class GetProductAvailableQtyCache implements GetProductAvailableQtyInterface
{
    /**
     * @param GetProductAvailableQty $getProductAvailableQty
     * @param CacheStorage $getProductAvailableQtyCacheStorage
     * @param bool $isReadonly
     */
    public function __construct(
        private readonly GetProductAvailableQty $getProductAvailableQty,
        private readonly CacheStorage $getProductAvailableQtyCacheStorage,
        private readonly bool $isReadonly = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): ?float
    {
        if (!$this->getProductAvailableQtyCacheStorage->has($sku, $stockId)) {
            $value = $this->getProductAvailableQty->execute($sku, $stockId);
            if (!$this->isReadonly) {
                $this->getProductAvailableQtyCacheStorage->set($sku, $stockId, $value);
            }
        } else {
            $value = $this->getProductAvailableQtyCacheStorage->get($sku, $stockId);
        }

        return $value;
    }
}
