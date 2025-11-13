<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface as ProductProcessor;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

class SkuListsProcessor
{
    /**
     * @var string
     */
    private string $connectionName = ResourceConnection::DEFAULT_CONNECTION;

    /**
     * @param GetSalableStatuses $getSalableStatuses
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexStructureInterface $indexStructure
     * @param IndexDataFiller $indexDataFiller
     * @param ProductProcessor[] $saleabilityChangesProcessorsPool
     */
    public function __construct(
        private readonly GetSalableStatuses $getSalableStatuses,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly IndexNameBuilder $indexNameBuilder,
        private readonly IndexStructureInterface $indexStructure,
        private readonly IndexDataFiller $indexDataFiller,
        private array $saleabilityChangesProcessorsPool = [],
    ) {
        // Sort processors by sort order
        uasort(
            $this->saleabilityChangesProcessorsPool,
            fn (ProductProcessor $a, ProductProcessor $b) => $a->getSortOrder() <=> $b->getSortOrder()
        );
    }

    /**
     * Reindex SkuListInStock list.
     *
     * @param SkuListInStock[] $skuListInStockList
     * @return void
     */
    public function reindexList(array $skuListInStockList): void
    {
        // Store products salable statuses before reindex
        $salableStatusesBefore = $this->getSalableStatuses->execute($skuListInStockList);

        foreach ($skuListInStockList as $skuListInStock) {
            $stockId = $skuListInStock->getStockId();
            if ($this->defaultStockProvider->getId() === $stockId) {
                continue;
            }

            $mainIndexName = $this->indexNameBuilder->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string) $stockId)
                ->setAlias(IndexAlias::MAIN->value)
                ->build();
            if (!$this->indexStructure->isExist($mainIndexName, $this->connectionName)) {
                $this->indexStructure->create($mainIndexName, $this->connectionName);
            }
            $this->indexDataFiller->fillIndex($mainIndexName, $skuListInStock, $this->connectionName);
        }

        // Store products salable statuses after reindex
        $salableStatusesAfter = $this->getSalableStatuses->execute($skuListInStockList);
        // Process products with changed salable statuses
        foreach ($this->saleabilityChangesProcessorsPool as $processor) {
            $processor->process($salableStatusesBefore, $salableStatusesAfter);
        }
    }
}
