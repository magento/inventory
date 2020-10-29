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
use Magento\InventoryCatalog\Model\GetProductIdsBySkusCache;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsCache;
use Magento\InventoryCatalog\Model\LegacyStockStatusCache;
use Magento\InventoryCatalog\Model\ResourceModel\GetProductTypesBySkusCache;

/**
 * Preload id/sku, sku/id and sku/type pairs into cache
 */
class PreloadCache implements ObserverInterface
{
    /**
     * @var GetProductTypesBySkusCache
     */
    private $getProductTypesBySkus;
    /**
     * @var GetProductIdsBySkusCache
     */
    private $getProductIdsBySkus;
    /**
     * @var GetSkusByProductIdsCache
     */
    private $getSkusByProductIds;
    /**
     * @var LegacyStockStatusCache
     */
    private $legacyStockStatusCache;

    /**
     * @param GetProductTypesBySkusCache $getProductTypesBySkus
     * @param GetProductIdsBySkusCache $getProductIdsBySkus
     * @param GetSkusByProductIdsCache $getSkusByProductIds
     * @param LegacyStockStatusCache $legacyStockStatusCache
     */
    public function __construct(
        GetProductTypesBySkusCache $getProductTypesBySkus,
        GetProductIdsBySkusCache $getProductIdsBySkus,
        GetSkusByProductIdsCache $getSkusByProductIds,
        LegacyStockStatusCache $legacyStockStatusCache
    ) {
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
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
            $this->getProductTypesBySkus->save($product->getSku(), $product->getTypeId());
            $this->getProductIdsBySkus->save($product->getSku(), (int) $product->getId());
            $this->getSkusByProductIds->save((int) $product->getId(), $product->getSku());
        }
        $productIds = array_keys($productCollection->getItems());
        if ($productIds) {
            $this->legacyStockStatusCache->preload($productIds);
        }
    }
}
