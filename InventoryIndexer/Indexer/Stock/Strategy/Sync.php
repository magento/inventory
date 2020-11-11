<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryIndexer\Indexer\Stock\Strategy;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\GetAllStockIds;
use Magento\InventoryIndexer\Indexer\Stock\IndexDataProviderByStockId;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexTableSwitcherInterface;

/**
 * Reindex stocks synchronously.
 */
class Sync
{
    /**
     * @var GetAllStockIds
     */
    private $getAllStockIds;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var IndexHandlerInterface
     */
    private $indexHandler;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexDataProviderByStockId
     */
    private $indexDataProviderByStockId;

    /**
     * @var IndexTableSwitcherInterface
     */
    private $indexTableSwitcher;

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism
     *
     * @param GetAllStockIds $getAllStockIds
     * @param IndexStructureInterface $indexStructureHandler
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexDataProviderByStockId $indexDataProviderByStockId
     * @param IndexTableSwitcherInterface $indexTableSwitcher
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        IndexStructureInterface $indexStructureHandler,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        IndexDataProviderByStockId $indexDataProviderByStockId,
        IndexTableSwitcherInterface $indexTableSwitcher
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->indexStructure = $indexStructureHandler;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexDataProviderByStockId = $indexDataProviderByStockId;
        $this->indexTableSwitcher = $indexTableSwitcher;
    }

    /**
     * Reindex all stocks.
     *
     * @return void
     */
    public function executeFull(): void
    {
        $stockIds = $this->getAllStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * Reindex single stock by id.
     *
     * @param int $stockId
     * @return void
     */
    public function executeRow(int $stockId): void
    {
        $this->executeList([$stockId]);
    }

    /**
     * Reindex list of stock by provided ids.
     *
     * @param int[] $stockIds
     * @return void
     */
    public function executeList(array $stockIds): void
    {
        foreach ($stockIds as $stockId) {
            $replicaIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_REPLICA)
                ->build();

            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            $this->indexStructure->delete($replicaIndexName, ResourceConnection::DEFAULT_CONNECTION);
            $this->indexStructure->create($replicaIndexName, ResourceConnection::DEFAULT_CONNECTION);

            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }

            $this->indexHandler->saveIndex(
                $replicaIndexName,
                $this->indexDataProviderByStockId->execute((int)$stockId),
                ResourceConnection::DEFAULT_CONNECTION
            );
            $this->indexTableSwitcher->switch($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            $this->indexStructure->delete($replicaIndexName, ResourceConnection::DEFAULT_CONNECTION);
        }
    }
}
