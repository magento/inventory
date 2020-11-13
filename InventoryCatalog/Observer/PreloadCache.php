<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryCatalog\Model\Cache\ProductIdsBySkusStorage;
use Magento\InventoryCatalog\Model\Cache\ProductSkusByIdsStorage;
use Magento\InventoryCatalog\Model\Cache\ProductTypesBySkusStorage;
use Magento\InventoryCatalog\Model\LegacyStockStatusCache;

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
     * @param ProductTypesBySkusStorage $productTypesBySkusStorage
     * @param ProductIdsBySkusStorage $productIdsBySkusStorage
     * @param ProductSkusByIdsStorage $productSkusByIdsStorage
     * @param LegacyStockStatusCache $legacyStockStatusCache
     */
    public function __construct(
        ProductTypesBySkusStorage $productTypesBySkusStorage,
        ProductIdsBySkusStorage $productIdsBySkusStorage,
        ProductSkusByIdsStorage $productSkusByIdsStorage,
        LegacyStockStatusCache $legacyStockStatusCache
    ) {
        $this->productTypesBySkusStorage = $productTypesBySkusStorage;
        $this->productIdsBySkusStorage = $productIdsBySkusStorage;
        $this->productSkusByIdsStorage = $productSkusByIdsStorage;
        $this->legacyStockStatusCache = $legacyStockStatusCache;
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
        }
        $productIds = array_keys($productCollection->getItems());
        if ($productIds) {
            $this->legacyStockStatusCache->execute($productIds);
        }
    }
}
