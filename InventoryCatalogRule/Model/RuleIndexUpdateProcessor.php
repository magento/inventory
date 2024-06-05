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
