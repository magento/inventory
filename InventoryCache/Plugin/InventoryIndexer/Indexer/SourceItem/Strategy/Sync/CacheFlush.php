<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryCache\Model\FlushCacheByCategoryIds;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

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
     * @var GetCategoryIdsByProductIds
     */
    private $getCategoryIdsByProductIds;

    /**
     * @var GetSalableStatuses
     */
    private $getSalableStatuses;

    /**
     * @var FlushCacheByCategoryIds
     */
    private $flushCategoryByCategoryIds;

    /**
     * @var GetProductsIdsToProcess
     */
    private $getProductsIdsToProcess;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param GetCategoryIdsByProductIds $getCategoryIdsByProductIds
     * @param FlushCacheByCategoryIds $flushCategoryByCategoryIds
     * @param GetSalableStatuses $getSalableStatuses
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        FlushCacheByProductIds $flushCacheByIds,
        GetCategoryIdsByProductIds $getCategoryIdsByProductIds,
        FlushCacheByCategoryIds $flushCategoryByCategoryIds,
        GetSalableStatuses $getSalableStatuses,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        IndexerRegistry $indexerRegistry
    ) {
        $this->flushCacheByIds = $flushCacheByIds;
        $this->getCategoryIdsByProductIds = $getCategoryIdsByProductIds;
        $this->flushCategoryByCategoryIds = $flushCategoryByCategoryIds;
        $this->getSalableStatuses = $getSalableStatuses;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Clean cache for specific products after source items reindex.
     *
     * @param Sync $subject
     * @param callable $proceed
     * @param array $sourceItemIds
     * @return void
     * @throws \Exception in case catalog product entity type hasn't been initialized.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteList(Sync $subject, callable $proceed, array $sourceItemIds) : void
    {
        $beforeSalableList = $this->getSalableStatuses->execute($sourceItemIds);
        $proceed($sourceItemIds);
        $afterSalableList = $this->getSalableStatuses->execute($sourceItemIds);
        $forceDefaultProcessing = !$this->indexerRegistry->get(InventoryIndexer::INDEXER_ID)->isScheduled();
        $productsIdsToFlush = $this->getProductsIdsToProcess->execute(
            $beforeSalableList,
            $afterSalableList,
            $forceDefaultProcessing
        );
        if (!empty($productsIdsToFlush)) {
            $categoryIds = $this->getCategoryIdsByProductIds->execute($productsIdsToFlush);
            $this->flushCacheByIds->execute($productsIdsToFlush);
            $this->flushCategoryByCategoryIds->execute($categoryIds);
        }
    }
}
