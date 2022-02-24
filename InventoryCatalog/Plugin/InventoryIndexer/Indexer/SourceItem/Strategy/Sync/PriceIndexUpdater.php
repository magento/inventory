<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Model\ResourceModel\GetSourceCodesBySourceItemIds;

/**
 * Reindex price after source item has reindexed.
 */
class PriceIndexUpdater
{
    /**
     * @var Processor
     */
    private $priceIndexProcessor;

    /**
     * @var GetSalableStatuses
     */
    private $getSalableStatuses;

    /**
     * @var GetProductsIdsToProcess
     */
    private $getProductsIdsToProcess;

    /**
     * @var GetSourceCodesBySourceItemIds
     */
    private $getSourceCodesBySourceItemIds;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param Processor $priceIndexProcessor
     * @param GetSalableStatuses $getSalableStatuses
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        Processor $priceIndexProcessor,
        GetSalableStatuses $getSalableStatuses,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->priceIndexProcessor = $priceIndexProcessor;
        $this->getSalableStatuses = $getSalableStatuses;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->getSourceCodesBySourceItemIds = $getSourceCodesBySourceItemIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Reindex product prices.
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
    ): void {
        $this->reindex($sourceItemIds, $proceed);
    }

    /**
     * Reindex product price
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
        $this->reindex([$sourceItemId], $proceed);
    }

    /**
     * Reindex prices after executing source item indexer
     *
     * @param array $sourceItemIds
     * @param callable $callback
     * @return void
     */
    private function reindex(array $sourceItemIds, callable $callback): void
    {
        $customSourceItemIds = [];
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        foreach ($this->getSourceCodesBySourceItemIds->execute($sourceItemIds) as $sourceItemId => $sourceCode) {
            if ($sourceCode !== $defaultSourceCode) {
                $customSourceItemIds[] = $sourceItemId;
            }
        }
        // in the case the source item is default source, the price indexer is executed according to indexer config
        if ($customSourceItemIds) {
            $beforeSalableList = $this->getSalableStatuses->execute($customSourceItemIds);
            $callback($sourceItemIds);
            $afterSalableList = $this->getSalableStatuses->execute($customSourceItemIds);
            $productsIdsToProcess = $this->getProductsIdsToProcess->execute($beforeSalableList, $afterSalableList);
            if (!empty($productsIdsToProcess)) {
                // force price reindex regardless of indexer mode.
                // price indexer cannot subscribe to source item changes (in scheduled mode)
                // because inventory_source_item does not have product id
                $this->priceIndexProcessor->reindexList($productsIdsToProcess, true);
            }
        } else {
            $callback($sourceItemIds);
        }
    }
}
