<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\InventoryCache\Model\FlushCacheByCategoryIds;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;
use Magento\InventoryIndexer\Model\ResourceModel\GetProductIdsBySourceItemIds;

/**
 * Clean cache for corresponding products after source item reindex.
 */
class CacheFlush
{
    /**
     * @var FlushCacheByProductIds
     */
    private $flushCacheByIds;

    /**
     * @var GetProductIdsBySourceItemIds
     */
    private $getProductIdsBySourceItemIds;

    /**
     * @var GetCategoryIdsByProductIds
     */
    private $getCategoryIdsByProductIds;

    /**
     * @var FlushCacheByCategoryIds
     */
    private $flushCategoryByCategoryIds;

    /**
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param GetProductIdsBySourceItemIds $getProductIdsBySourceItemIds
     * @param GetCategoryIdsByProductIds $getCategoryIdsByProductIds
     * @param FlushCacheByCategoryIds $flushCategoryByCategoryIds
     */
    public function __construct(
        FlushCacheByProductIds $flushCacheByIds,
        GetProductIdsBySourceItemIds $getProductIdsBySourceItemIds,
        GetCategoryIdsByProductIds $getCategoryIdsByProductIds,
        FlushCacheByCategoryIds $flushCategoryByCategoryIds
    ) {
        $this->flushCacheByIds = $flushCacheByIds;
        $this->getProductIdsBySourceItemIds = $getProductIdsBySourceItemIds;
        $this->getCategoryIdsByProductIds = $getCategoryIdsByProductIds;
        $this->flushCategoryByCategoryIds = $flushCategoryByCategoryIds;
    }

    /**
     * Clean cache for specific products after source items reindex.
     *
     * @param Sync $subject
     * @param void $result
     * @param array $sourceItemIds
     * @throws \Exception in case catalog product entity type hasn't been initialize.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteList(Sync $subject, $result, array $sourceItemIds)
    {
        $productIds = $this->getProductIdsBySourceItemIds->execute($sourceItemIds);
        $categoryIds = $this->getCategoryIdsByProductIds->execute($productIds);
        $this->flushCategoryByCategoryIds->execute($categoryIds);
        $this->flushCacheByIds->execute($productIds);
    }
}
