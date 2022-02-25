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
use Magento\InventoryIndexer\Model\ResourceModel\GetProductIdsBySourceItemIds;
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
     * @var GetProductIdsBySourceItemIds
     */
    private $productIdsBySourceItemIds;

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
     * @param GetProductIdsBySourceItemIds $productIdsBySourceItemIds
     * @param GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        Processor $priceIndexProcessor,
        GetProductIdsBySourceItemIds $productIdsBySourceItemIds,
        GetSourceCodesBySourceItemIds $getSourceCodesBySourceItemIds,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->priceIndexProcessor = $priceIndexProcessor;
        $this->productIdsBySourceItemIds = $productIdsBySourceItemIds;
        $this->getSourceCodesBySourceItemIds = $getSourceCodesBySourceItemIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Reindex product prices.
     *
     * @param Sync $subject
     * @param void $result
     * @param array $sourceItemIds
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteList(
        Sync $subject,
        $result,
        array $sourceItemIds
    ): void {
        $customSourceItemIds = [];
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        foreach ($this->getSourceCodesBySourceItemIds->execute($sourceItemIds) as $sourceItemId => $sourceCode) {
            if ($sourceCode !== $defaultSourceCode) {
                $customSourceItemIds[] = $sourceItemId;
            }
        }
        // In the case the source item is default source,
        // the price indexer will be executed according to indexer.xml configuration
        if ($customSourceItemIds) {
            $productIds = $this->productIdsBySourceItemIds->execute($customSourceItemIds);
            if (!empty($productIds)) {
                $this->priceIndexProcessor->reindexList($productIds, true);
            }
        }
    }
}
