<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatusesCached;
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
     * @var GetSourceCodesBySourceItemIds
     */
    private $getSourceCodesBySourceItemIds;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetSalableStatusesCached
     */
    private $getSalableStatusesCached;

    /**
     * @var GetProductsIdsToProcess
     */
    private $getProductsIdsToProcess;

    /**
     * @param Processor $priceIndexProcessor
     * @param GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetSalableStatusesCached $getSalableStatusesCached
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     */
    public function __construct(
        Processor $priceIndexProcessor,
        GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetSalableStatusesCached $getSalableStatusesCached,
        GetProductsIdsToProcess $getProductsIdsToProcess
    ) {
        $this->priceIndexProcessor = $priceIndexProcessor;
        $this->getSourceCodesBySourceItemIds = $getSourceCodesBySourceItemIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getSalableStatusesCached = $getSalableStatusesCached;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
    }

    /**
     * Reindex product prices.
     *
     * @param Sync $subject
     * @param callable $proceed
     * @param array $sourceItemIds
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecuteList(Sync $subject, callable $proceed, array $sourceItemIds) : void
    {
        $customSourceItemIds = [];
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        foreach ($this->getSourceCodesBySourceItemIds->execute($sourceItemIds) as $sourceItemId => $sourceCode) {
            if ($sourceCode !== $defaultSourceCode) {
                $customSourceItemIds[] = $sourceItemId;
            }
        }
        $beforeSalableList = $this->getSalableStatusesCached->execute($customSourceItemIds, 'before');
        $proceed($sourceItemIds);
        $afterSalableList = $this->getSalableStatusesCached->execute($customSourceItemIds, 'after');

        $productsIdsToReindex = $this->getProductsIdsToProcess->execute($beforeSalableList, $afterSalableList);
        if (!empty($productsIdsToReindex)) {
            $this->priceIndexProcessor->reindexList($productsIdsToReindex, true);
        }
    }
}
