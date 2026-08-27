<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

/**
 * Synchronizes stock item data from the stock registry storage into the inventory cache storage.
 */
class StockRegistryStockItemCache implements CacheInterface
{
    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockRegistryStorage $stockRegistryStorage
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param CacheStorage $cacheStorage
     */
    public function __construct(
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly StockRegistryStorage $stockRegistryStorage,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly CacheStorage $cacheStorage
    ) {
    }

    /**
     * @inheritDoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        $skus = array_filter($skus, fn ($sku) => $this->cacheStorage->get((string) $sku) === null);
        try {
            $idsBySku = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            $idsBySku = [];
        }
        $scopeId = (int) $this->stockConfiguration->getDefaultScopeId();
        foreach ($idsBySku as $sku => $productId) {
            $item = $this->stockRegistryStorage->getStockItem((int) $productId, $scopeId);
            if ($item) {
                $this->cacheStorage->set((string) $sku, $item);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clean(array $skus, ?int $stockId): void
    {
        try {
            $idsBySku = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            $idsBySku = [];
        }
        $scopeId = (int) $this->stockConfiguration->getDefaultScopeId();
        foreach ($idsBySku as $id) {
            $this->stockRegistryStorage->removeStockItem((int) $id, $scopeId);
        }
    }
}
