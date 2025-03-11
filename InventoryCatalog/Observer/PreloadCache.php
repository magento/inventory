<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryCatalog\Model\Cache\ProductIdsBySkusStorage;
use Magento\InventoryCatalog\Model\Cache\ProductSkusByIdsStorage;
use Magento\InventoryCatalog\Model\Cache\ProductTypesBySkusStorage;
use Magento\InventoryCatalog\Model\LegacyStockStatusCache;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage as StockItemDataCacheStorage;
use Magento\Inventory\Model\GetStockItemData\IsProductAssignedToStockCacheStorage;

/**
 * Preload id/sku, sku/id and sku/type pairs into cache
 */
class PreloadCache implements ObserverInterface
{
    /**
     * @var ProductTypesBySkusStorage
     */
    private $productTypesBySkusStorage;

    /**
     * @var ProductIdsBySkusStorage
     */
    private $productIdsBySkusStorage;

    /**
     * @var ProductSkusByIdsStorage
     */
    private $productSkusByIdsStorage;

    /**
     * @var LegacyStockStatusCache
     */
    private $legacyStockStatusCache;

    /**
     * @var StockItemDataCacheStorage
     */
    private $stockItemDataCacheStorage;

    /**
     * @var GetStockItemData
     */
    private $stockRegistry;

    /**
     * @var IsProductAssignedToStockCacheStorage
     */
    private IsProductAssignedToStockCacheStorage $cacheStorage;

    /**
     * @param ProductTypesBySkusStorage $productTypesBySkusStorage
     * @param ProductIdsBySkusStorage $productIdsBySkusStorage
     * @param ProductSkusByIdsStorage $productSkusByIdsStorage
     * @param LegacyStockStatusCache $legacyStockStatusCache
     * @param StockItemDataCacheStorage $stockItemDataCacheStorage
     * @param StockRegistryInterface $stockRegistry
     * @param IsProductAssignedToStockCacheStorage $cacheStorage
     */
    public function __construct(
        ProductTypesBySkusStorage $productTypesBySkusStorage,
        ProductIdsBySkusStorage $productIdsBySkusStorage,
        ProductSkusByIdsStorage $productSkusByIdsStorage,
        LegacyStockStatusCache $legacyStockStatusCache,
        StockItemDataCacheStorage $stockItemDataCacheStorage,
        StockRegistryInterface $stockRegistry,
        IsProductAssignedToStockCacheStorage $cacheStorage
    ) {
        $this->productTypesBySkusStorage = $productTypesBySkusStorage;
        $this->productIdsBySkusStorage = $productIdsBySkusStorage;
        $this->productSkusByIdsStorage = $productSkusByIdsStorage;
        $this->legacyStockStatusCache = $legacyStockStatusCache;
        $this->stockItemDataCacheStorage = $stockItemDataCacheStorage;
        $this->stockRegistry = $stockRegistry;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Collection $productCollection */
        $productCollection = $observer->getData('collection');

        /** @var Product $product */
        foreach ($productCollection->getItems() as $product) {
            $this->productTypesBySkusStorage->set((string) $product->getSku(), (string) $product->getTypeId());
            $this->productIdsBySkusStorage->set((string) $product->getSku(), (int) $product->getId());
            $this->productSkusByIdsStorage->set((int) $product->getId(), (string) $product->getSku());
            $stockData  = $this->stockRegistry->getStockItemBySku($product->getSku());
            $stockCache = ['quantity' => $stockData->getQty(), 'is_salable' => $stockData->getIsInStock()];
            $this->stockItemDataCacheStorage->set(Stock::DEFAULT_STOCK_ID, $product->getSku(), $stockCache);
            $this->stockItemDataCacheStorage->delete(Stock::DEFAULT_STOCK_ID, $product->getSku());
            $this->cacheStorage->delete(Stock::DEFAULT_STOCK_ID, $product->getSku());
        }
        $productIds = array_keys($productCollection->getItems());
        if ($productIds) {
            $this->legacyStockStatusCache->execute($productIds);
        }
    }
}
