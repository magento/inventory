<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;

class FullTextIndexUpdateProcessor implements CompositeProductProcessorInterface
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
    private $fulltextIndexProcessor;

    /**
     * @var GetProductsIdsToProcess
     */
    private GetProductsIdsToProcess $getProductsIdsToProcess;

    /**
     * @param Processor $fulltextIndexProcessor
     * @param GetProductsIdsToProcess $getProductsIdsToProcess
     * @param int $sortOrder
     */
    public function __construct(
        Processor $fulltextIndexProcessor,
        GetProductsIdsToProcess $getProductsIdsToProcess,
        int $sortOrder = 20
    ) {
        $this->fulltextIndexProcessor = $fulltextIndexProcessor;
        $this->getProductsIdsToProcess = $getProductsIdsToProcess;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Perform fulltext index update for specific products after source items reindex.
     *
     * @param array $saleableStatusesBeforeSync
     * @param array $saleableStatusesAfterSync
     * @return void
     */
    public function process(
        array $saleableStatusesBeforeSync,
        array $saleableStatusesAfterSync
    ): void {
        $productsIdsToProcess = $this->getProductsIdsToProcess->execute(
            $saleableStatusesBeforeSync,
            $saleableStatusesAfterSync
        );

        if (!empty($productsIdsToProcess)) {
            $this->fulltextIndexProcessor->reindexList($productsIdsToProcess, true);
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
