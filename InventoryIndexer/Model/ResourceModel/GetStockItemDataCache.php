<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage;

/**
 * @inheritdoc
 */
class GetStockItemDataCache implements GetStockItemDataInterface
{
    /**
     * @param GetStockItemData $getStockItemData
     * @param CacheStorage $cacheStorage
     * @param bool $isReadonly
     */
    public function __construct(
        private readonly GetStockItemData $getStockItemData,
        private readonly CacheStorage $cacheStorage,
        private readonly bool $isReadonly = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): ?array
    {
        if ($this->cacheStorage->get($stockId, $sku)) {
            return $this->cacheStorage->get($stockId, $sku);
        }
        /** @var array $stockItemData */
        $stockItemData =  $this->getStockItemData->execute($sku, $stockId);
        /* Add to cache a new item */
        if (!empty($stockItemData) && !$this->isReadonly) {
            $this->cacheStorage->set($stockId, $sku, $stockItemData);
        }

        return $stockItemData;
    }
}
