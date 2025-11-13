<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage;

/**
 * @inheritdoc
 */
class GetStockItemDataCache implements GetStockItemDataInterface
{
    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @param GetStockItemData $getStockItemData
     * @param CacheStorage|null $cacheStorage
     */
    public function __construct(
        GetStockItemData $getStockItemData,
        ?CacheStorage $cacheStorage = null
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->cacheStorage = $cacheStorage ?: ObjectManager::getInstance()
            ->get(CacheStorage::class);
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
        if (!empty($stockItemData)) {
            $this->cacheStorage->set($stockId, $sku, $stockItemData);
        }

        return $stockItemData;
    }
}
