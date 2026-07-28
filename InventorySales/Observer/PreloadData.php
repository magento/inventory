<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySales\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PreloadData implements ObserverInterface
{
    /**
     * @param CacheInterface $cache
     * @param StoreManagerInterface $storeManager
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly StoreManagerInterface $storeManager,
        private readonly StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Collection $productCollection */
        $productCollection = $observer->getData('collection');
        $storeId = $productCollection->getStoreId();
        if ($storeId && $this->scopeConfig->isSetFlag(
            'cataloginventory/options/enable_inventory_check',
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
            $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
            $this->cache->warmup($productCollection->getColumnValues('sku'), $stockId);
        }
    }
}
