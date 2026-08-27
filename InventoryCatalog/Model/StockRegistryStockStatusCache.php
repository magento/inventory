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
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Synchronizes stock status data from the stock registry storage into the inventory cache storage.
 */
class StockRegistryStockStatusCache implements CacheInterface
{
    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockRegistryStorage $stockRegistryStorage
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param CacheStorage $cacheStorage
     */
    public function __construct(
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly StockRegistryStorage $stockRegistryStorage,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly CacheStorage $cacheStorage
    ) {
    }

    /**
     * @inheritDoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        if ($stockId === $this->defaultStockProvider->getId()) {
            $skus = array_filter($skus, fn ($sku) => $this->cacheStorage->get($stockId, (string) $sku) === null);
            try {
                $idsBySku = $this->getProductIdsBySkus->execute($skus);
            } catch (NoSuchEntityException $skuNotFoundInCatalog) {
                $idsBySku = [];
            }
            $scopeId = (int) $this->stockConfiguration->getDefaultScopeId();
            foreach ($idsBySku as $sku => $productId) {
                $item = $this->stockRegistryStorage->getStockStatus((int) $productId, $scopeId);
                if ($item) {
                    $this->cacheStorage->set(
                        $stockId,
                        (string) $sku,
                        [
                            GetStockItemDataInterface::QUANTITY => $item->getQty(),
                            GetStockItemDataInterface::IS_SALABLE => $item->getStockStatus(),
                        ]
                    );
                }
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
            $this->stockRegistryStorage->removeStockStatus((int) $id, $scopeId);
        }
    }
}
