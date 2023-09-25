<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem\Strategy;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\IndexDataBySkuListProvider;
use Magento\InventoryIndexer\Indexer\Stock\PrepareReservationsIndexData;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

/**
 * Reindex source items synchronously.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Sync
{
    /**
     * @var GetSkuListInStock
     */
    private $getSkuListInStock;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var IndexHandlerInterface
     */
    private $indexHandler;

    /**
     * @var IndexDataBySkuListProvider
     */
    private $indexDataBySkuListProvider;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var ReservationsIndexTable
     */
    private $reservationsIndexTable;

    /**
     * @var PrepareReservationsIndexData
     */
    private $prepareReservationsIndexData;

    /**
     * @var GetSalableStatuses
     */
    private $getSalableStatuses;

    /**
     * @var array
     */
    private array $saleabilityChangesProcessorsPool;

    /**
     * $indexStructure is reserved name for construct variable (in index internal mechanism)
     *
     * @param GetSkuListInStock $getSkuListInStockToUpdate
     * @param IndexStructureInterface $indexStructureHandler
     * @param IndexHandlerInterface $indexHandler
     * @param IndexDataBySkuListProvider $indexDataBySkuListProvider
     * @param IndexNameBuilder $indexNameBuilder
     * @param StockIndexer $stockIndexer
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ReservationsIndexTable $reservationsIndexTable
     * @param PrepareReservationsIndexData $prepareReservationsIndexData
     * @param GetSalableStatuses $getSalableStatuses
     * @param array $saleabilityChangesProcessorsPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        GetSkuListInStock $getSkuListInStockToUpdate,
        IndexStructureInterface $indexStructureHandler,
        IndexHandlerInterface $indexHandler,
        IndexDataBySkuListProvider $indexDataBySkuListProvider,
        IndexNameBuilder $indexNameBuilder,
        StockIndexer $stockIndexer,
        DefaultStockProviderInterface $defaultStockProvider,
        ReservationsIndexTable $reservationsIndexTable,
        PrepareReservationsIndexData $prepareReservationsIndexData,
        GetSalableStatuses $getSalableStatuses,
        array $saleabilityChangesProcessorsPool = []
    ) {
        $this->getSkuListInStock = $getSkuListInStockToUpdate;
        $this->indexStructure = $indexStructureHandler;
        $this->indexHandler = $indexHandler;
        $this->indexDataBySkuListProvider = $indexDataBySkuListProvider;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->stockIndexer = $stockIndexer;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->reservationsIndexTable = $reservationsIndexTable;
        $this->prepareReservationsIndexData = $prepareReservationsIndexData;
        $this->getSalableStatuses = $getSalableStatuses;
        $this->saleabilityChangesProcessorsPool = $saleabilityChangesProcessorsPool;
    }

    /**
     * Reindex list of source items by provided ids.
     *
     * @param int[] $sourceItemIds
     */
    public function executeList(array $sourceItemIds) : void
    {
        // Store products salable statuses before reindex
        $salableStatusesBefore = $this->getSalableStatuses->execute($sourceItemIds);

        $skuListInStockList = $this->getSkuListInStock->execute($sourceItemIds);

        foreach ($skuListInStockList as $skuListInStock) {
            $stockId = $skuListInStock->getStockId();
            if ($this->defaultStockProvider->getId() === $stockId) {
                continue;
            }

            $skuList = $skuListInStock->getSkuList();

            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }

            $this->indexHandler->cleanIndex(
                $mainIndexName,
                new ArrayIterator($skuList),
                ResourceConnection::DEFAULT_CONNECTION
            );

            $this->reservationsIndexTable->createTable($stockId);
            $this->prepareReservationsIndexData->execute($stockId);

            $indexData = $this->indexDataBySkuListProvider->execute($stockId, $skuList);
            $this->indexHandler->saveIndex(
                $mainIndexName,
                $indexData,
                ResourceConnection::DEFAULT_CONNECTION
            );

            $this->reservationsIndexTable->dropTable($stockId);
        }

        // Store products salable statuses after reindex
        $salableStatusesAfter = $this->getSalableStatuses->execute($sourceItemIds);
        // Process products with changed salable statuses
        $this->processProductsWithChangedSaleability($sourceItemIds, $salableStatusesBefore, $salableStatusesAfter);
    }

    /**
     * Process products with changed salable statuses
     *
     * @param array $sourceItemIds
     * @param array $saleableStatusesBefore
     * @param array $saleableStatusesAfter
     * @return void
     */
    private function processProductsWithChangedSaleability(
        array $sourceItemIds,
        array $saleableStatusesBefore,
        array $saleableStatusesAfter
    ): void {
        $processors = $this->saleabilityChangesProcessorsPool;

        // Sort processors by sort order
        uasort($processors, function ($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        foreach ($processors as $processor) {
            $processor->process($sourceItemIds, $saleableStatusesBefore, $saleableStatusesAfter);
        }
    }

    /**
     * Reindex all source items
     *
     * @return void
     */
    public function executeFull() : void
    {
        $this->stockIndexer->executeFull();
    }

    /**
     * Reindex single source item by id
     *
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId) : void
    {
        $this->executeList([$sourceItemId]);
    }
}
