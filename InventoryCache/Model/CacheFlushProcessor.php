<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;

class CacheFlushProcessor implements CompositeProductProcessorInterface
{
    /**
     * Processor sort order
     *
     * @var int
     */
    private $sortOrder;

    /**
     * @var FlushCacheByProductIds
     */
    private $flushCacheByIds;

    /**
     * @var GetCategoryIdsByProductIds
     */
    private $getCategoryIdsByProductIds;

    /**
     * @var FlushCacheByCategoryIds
     */
    private $flushCategoryByCategoryIds;

    /**
     * @var GetProductsIdsToProcess
     */
    private GetProductsIdsToProcess $getProductsIdsToProcess;

    /**
     * @var IndexerRegistry
     */
    private IndexerRegistry $indexerRegistry;

    /**
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param GetCategoryIdsByProductIds $getCategoryIdsByProductIds
     * @param FlushCacheByCategoryIds $flushCategoryByCategoryIds
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param IndexerRegistry $indexerRegistry
     * @param int $sortOrder
     */
    public function __construct(
        FlushCacheByProductIds $flushCacheByIds,
        GetCategoryIdsByProductIds $getCategoryIdsByProductIds,
        FlushCacheByCategoryIds $flushCategoryByCategoryIds,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        IndexerRegistry $indexerRegistry,
        int $sortOrder = 30
    ) {
        $this->flushCacheByIds = $flushCacheByIds;
        $this->getCategoryIdsByProductIds = $getCategoryIdsByProductIds;
        $this->flushCategoryByCategoryIds = $flushCategoryByCategoryIds;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->indexerRegistry = $indexerRegistry;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Clean cache for specific products after source items reindex.
     *
     * @param array $sourceItemIds
     * @param array $saleableStatusesBeforeSync
     * @param array $saleableStatusesAfterSync
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(
        array $sourceItemIds,
        array $saleableStatusesBeforeSync,
        array $saleableStatusesAfterSync
    ): void {
        $forceDefaultProcessing = !$this->indexerRegistry->get(InventoryIndexer::INDEXER_ID)->isScheduled();

        $productsIdsToFlush = $this->getProductsIdsToProcess->execute(
            $saleableStatusesBeforeSync,
            $saleableStatusesAfterSync,
            $forceDefaultProcessing
        );

        if (!empty($productsIdsToFlush)) {
            $categoryIds = $this->getCategoryIdsByProductIds->execute($productsIdsToFlush);
            $this->flushCacheByIds->execute($productsIdsToFlush);
            $this->flushCategoryByCategoryIds->execute($categoryIds);
        }
    }

    /**
     * @inheritdoc
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
