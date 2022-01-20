<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemDataCache;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage;

class GetStockItemDataCachePlugin
{
    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @param CacheStorage $cacheStorage
     * @param GetStockItemData $getStockItemData
     */
    public function __construct(
        CacheStorage $cacheStorage,
        GetStockItemData $getStockItemData
    ) {
        $this->cacheStorage = $cacheStorage;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * Cache the result of service to avoid duplicate queries to the database.
     *
     * @param GetStockItemDataCache $subject
     * @param callable $proceed
     * @param string $sku
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        GetStockItemDataCache $subject,
        callable $proceed,
        string $sku,
        int $stockId
    ): ?array {
        if ($this->cacheStorage->get($stockId, $sku)) {
            return $this->cacheStorage->get($stockId, $sku);
        }

        /** @var StockItemInterface $item */
        $stockItemData =  $this->getStockItemData->execute($sku, $stockId);
        /* Avoid add to cache a new item */
        if (!empty($stockItemData)) {
            $this->cacheStorage->set($stockId, $sku, $stockItemData);
        }

        return $stockItemData;
    }
}
