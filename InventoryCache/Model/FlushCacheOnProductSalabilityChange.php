<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
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
