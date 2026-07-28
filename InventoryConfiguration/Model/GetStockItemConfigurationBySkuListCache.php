<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Model\GetStockItemConfigurationBySkuListCacheInterface;

class GetStockItemConfigurationBySkuListCache implements GetStockItemConfigurationBySkuListCacheInterface
{
    /**
     * @param GetLegacyStockItemsCache $getLegacyStockItemsCache
     */
    public function __construct(
        private readonly GetLegacyStockItemsCache $getLegacyStockItemsCache,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        $this->getLegacyStockItemsCache->warmup($skus, $stockId);
    }

    /**
     * @inheritdoc
     */
    public function clean(array $skus, ?int $stockId): void
    {
        $this->getLegacyStockItemsCache->clean($skus, $stockId);
    }
}
