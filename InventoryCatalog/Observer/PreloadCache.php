<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
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

/**
 * Seeds id/sku, sku/id and sku/type caches to avoid multiple queries down the line during inventory checks.
 */
class PreloadCache implements ObserverInterface
{
    /**
     * @param ProductIdsBySkusStorage $productIdsBySkusStorage
     * @param ProductSkusByIdsStorage $productSkusByIdsStorage
     * @param ProductTypesBySkusStorage $productTypesBySkusStorage
     */
    public function __construct(
        private readonly ProductIdsBySkusStorage $productIdsBySkusStorage,
        private readonly ProductSkusByIdsStorage $productSkusByIdsStorage,
        private readonly ProductTypesBySkusStorage $productTypesBySkusStorage
    ) {
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
    }
}
