<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogRule\Model;

use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;

class RuleIndexUpdateProcessor implements CompositeProductProcessorInterface
{
    /**
     * Processor sort order
     *
     * @var int
     */
    private $sortOrder;

    /**
     * @var ProductRuleProcessor
     */
    private $productRuleProcessor;

    /**
     * @var GetProductsIdsToProcess
     */
    private $getProductsIdsToProcess;

    /**
     * @param ProductRuleProcessor $productRuleProcessor
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param int $sortOrder
     */
    public function __construct(
        ProductRuleProcessor $productRuleProcessor,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        int $sortOrder = 8
    ) {
        $this->productRuleProcessor = $productRuleProcessor;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Product rule index update for specific products after source items reindex.
     *
     * @param array $saleableStatusesBeforeSync
     * @param array $saleableStatusesAfterSync
     * @return void
     */
    public function process(
        array $saleableStatusesBeforeSync,
        array $saleableStatusesAfterSync
    ): void {
        $productsIdsToReindex = $this->getProductsIdsToProcess->execute(
            $saleableStatusesBeforeSync,
            $saleableStatusesAfterSync
        );
        if (!empty($productsIdsToReindex)) {
            $this->productRuleProcessor->reindexList($productsIdsToReindex, true);
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
