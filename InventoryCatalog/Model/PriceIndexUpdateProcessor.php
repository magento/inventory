<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;

class PriceIndexUpdateProcessor implements CompositeProductProcessorInterface
{
    /**
     * Processor sort order
     *
     * @var int
     */
    private $sortOrder;

    /**
     * @var Processor
     */
    private $priceIndexProcessor;

    /**
     * @var GetProductsIdsToProcess
     */
    private GetProductsIdsToProcess $getProductsIdsToProcess;

    /**
     * @param Processor $priceIndexProcessor
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param int $sortOrder
     */
    public function __construct(
        Processor $priceIndexProcessor,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        int $sortOrder = 10
    ) {
        $this->priceIndexProcessor = $priceIndexProcessor;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Price index update for specific products after source items reindex.
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
            $this->priceIndexProcessor->reindexList($productsIdsToReindex, true);
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
