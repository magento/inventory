<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Model\ResourceModel\GetProductIdsBySourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;

/**
 * Reindex fulltext after source item has reindexed.
 */
class FulltextIndexUpdater
{
    /**
     * @var Processor
     */
    private $fulltextIndexProcessor;

    /**
     * @var GetProductIdsBySourceItemIds
     */
    private $productIdsBySourceItemIds;

    /**
     * @var GetSalableStatuses
     */
    private $getSalableStatuses;

    /**
     * @var GetProductsIdsToProcess
     */
    private $getProductsIdsToProcess;

    /**
     * @param Processor $fulltextIndexProcessor
     * @param GetProductIdsBySourceItemIds $productIdsBySourceItemIds
     * @param GetSalableStatuses $getSalableStatuses
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     */
    public function __construct(
        Processor $fulltextIndexProcessor,
        GetProductIdsBySourceItemIds $productIdsBySourceItemIds,
        GetSalableStatuses $getSalableStatuses,
        GetProductsIdsToProcess $getProductsIdsToProcess
    ) {
        $this->fulltextIndexProcessor = $fulltextIndexProcessor;
        $this->productIdsBySourceItemIds = $productIdsBySourceItemIds;
        $this->getSalableStatuses = $getSalableStatuses;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
    }

    /**
     * Reindex fulltext entities
     *
     * @param Sync $subject
     * @param callable $proceed
     * @param array $sourceItemIds
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteList(
        Sync $subject,
        callable $proceed,
        array $sourceItemIds
    ) {
        $beforeSalableList = $this->getSalableStatuses->execute($sourceItemIds);
        $proceed($sourceItemIds);
        $afterSalableList = $this->getSalableStatuses->execute($sourceItemIds);
        $productsIdsToProcess = $this->getProductsIdsToProcess->execute($beforeSalableList, $afterSalableList);
        if (!empty($productsIdsToProcess)) {
            $this->fulltextIndexProcessor->reindexList($productsIdsToProcess, true);
        }
    }

    /**
     * Reindex fulltext entity
     *
     * @param Sync $subject
     * @param callable $proceed
     * @param int $sourceItemId
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteRow(
        Sync $subject,
        callable $proceed,
        int $sourceItemId
    ) {
        $beforeSalableList = $this->getSalableStatuses->execute([$sourceItemId]);
        $proceed($sourceItemId);
        $afterSalableList = $this->getSalableStatuses->execute([$sourceItemId]);
        $productsIdsToProcess = $this->getProductsIdsToProcess->execute($beforeSalableList, $afterSalableList);
        if (!empty($productsIdsToProcess)) {
            $this->fulltextIndexProcessor->reindexList($productsIdsToProcess, true);
        }
    }
}
