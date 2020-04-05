<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\InventoryIndexer\Indexer\Source\GetAssignedStockIds;

/**
 * Source Item indexer
 *
 * @api
 */
class SourceItemIndexer
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
     * @var GetAssignedStockIds
     */
    private $getAssignedStockIds;

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
     * @param GetAssignedStockIds $getAssignedStockIds
     */
    public function __construct(
        GetSkuListInStock $getSkuListInStockToUpdate,
        IndexStructureInterface $indexStructureHandler,
        IndexHandlerInterface $indexHandler,
        IndexDataBySkuListProvider $indexDataBySkuListProvider,
        IndexNameBuilder $indexNameBuilder,
        StockIndexer $stockIndexer,
        DefaultStockProviderInterface $defaultStockProvider,
        GetAssignedStockIds $getAssignedStockIds
    ) {
        $this->getSkuListInStock = $getSkuListInStockToUpdate;
        $this->indexStructure = $indexStructureHandler;
        $this->indexHandler = $indexHandler;
        $this->indexDataBySkuListProvider = $indexDataBySkuListProvider;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->stockIndexer = $stockIndexer;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getAssignedStockIds = $getAssignedStockIds;
    }

    /**
     * Run a full reindex
     *
     * @return void
     */
    public function executeFull()
    {
        $this->stockIndexer->executeFull();
    }

    /**
     * Execute the indexer for a single source item id
     *
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId)
    {
        $this->executeList([$sourceItemId]);
    }

    /**
     * Exec the indexer for the provided source item ids
     *
     * @param array $sourceItemIds
     * @return void
     */
    public function executeList(array $sourceItemIds)
    {
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
                new \ArrayIterator($skuList),
                ResourceConnection::DEFAULT_CONNECTION
            );

            $indexData = $this->indexDataBySkuListProvider->execute($stockId, $skuList);
            $this->indexHandler->saveIndex(
                $mainIndexName,
                $indexData,
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
    }

    /**
     * Individually clear out source items from the index that no longer link to a source.
     * More performant than reindexing the entire stock list.
     *
     * @param array $sourceCodeSkuMap [source_code => [sku, sku, sku], ....]
     * @return void
     */
    public function executeOrphanList(array $sourceCodeSkuMap)
    {
        if (empty($sourceCodeSkuMap)) {
            return;
        }
        foreach ($sourceCodeSkuMap as $sourceCode => $skuList) {
            $stockId = $this->getAssignedStockIds->execute([$sourceCode]);
            if (empty($stockId)) {
                return;
            }

            if ($this->defaultStockProvider->getId() === $stockId[0]) {
                continue;
            }

            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId[0])
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }

            $this->indexHandler->cleanIndex(
                $mainIndexName,
                new \ArrayIterator($skuList),
                ResourceConnection::DEFAULT_CONNECTION
            );

        }

    }
}