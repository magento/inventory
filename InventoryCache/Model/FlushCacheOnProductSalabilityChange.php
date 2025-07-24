<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;

class FlushCacheOnProductSalabilityChange implements ProductSalabilityChangeProcessorInterface
{
    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param GetCategoryIdsByProductIds $getCategoryIdsByProductIds
     * @param FlushCacheByCategoryIds $flushCategoryByCategoryIds
     */
    public function __construct(
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly FlushCacheByProductIds $flushCacheByIds,
        private readonly GetCategoryIdsByProductIds $getCategoryIdsByProductIds,
        private readonly FlushCacheByCategoryIds $flushCategoryByCategoryIds
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): void
    {
        $ids = array_values($this->getProductIdsBySkus->execute($skus));
        if (empty($ids)) {
            return;
        }
        $categoryIds = $this->getCategoryIdsByProductIds->execute($ids);
        $this->flushCacheByIds->execute($ids);
        $this->flushCategoryByCategoryIds->execute($categoryIds);
    }
}
