<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Inventory\Model\AreProductsAssignedToStockCache;
use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * This class is a wrapper for AreProductsAssignedToStockCache, which allows to skip warming data for default stock.
 *
 * @see \Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface::execute
 */
class AreProductsAssignedToStockCacheProxy implements CacheInterface
{
    /**
     * @param AreProductsAssignedToStockCache $areProductsAssignedToStockCache
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        private readonly AreProductsAssignedToStockCache $areProductsAssignedToStockCache,
        private readonly DefaultStockProviderInterface $defaultStockProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        if ($stockId !== $this->defaultStockProvider->getId()) {
            $this->areProductsAssignedToStockCache->warmup($skus, $stockId);
        }
    }

    /**
     * @inheritDoc
     */
    public function clean(array $skus, ?int $stockId): void
    {
        $this->areProductsAssignedToStockCache->clean($skus, $stockId);
    }
}
