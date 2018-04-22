<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\Framework\MultiDimensionalIndexer\Alias;
use Magento\Framework\MultiDimensionalIndexer\IndexHandlerInterface;
use Magento\Framework\MultiDimensionalIndexer\IndexNameBuilder;
use Magento\Framework\MultiDimensionalIndexer\IndexStructureInterface;
use Magento\Framework\MultiDimensionalIndexer\IndexTableSwitcherInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

/**
 * Stock indexer
 * Extension point for indexation
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockIndexer
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
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism
     *
     * @param GetAllStockIds $getAllStockIds
     * @param IndexStructureInterface $indexStructureHandler
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexDataProviderByStockId $indexDataProviderByStockId
     * @param IndexTableSwitcherInterface $indexTableSwitcher
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        IndexStructureInterface $indexStructureHandler,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        IndexDataProviderByStockId $indexDataProviderByStockId,
        IndexTableSwitcherInterface $indexTableSwitcher,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->indexStructure = $indexStructureHandler;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexDataProviderByStockId = $indexDataProviderByStockId;
        $this->indexTableSwitcher = $indexTableSwitcher;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @return void
     * @throws StateException
     */
    public function executeFull(): void
    {
        $stockIds = $this->getAllStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * @param int $stockId
     * @return void
     * @throws StateException
     */
    public function executeRow(int $stockId): void
    {
        $this->executeList([$stockId]);
    }

    /**
     * @param array $stockIds
     * @return void
     * @throws StateException
     */
    public function executeList(array $stockIds): void
    {
        foreach ($stockIds as $stockId) {
            if ($this->defaultStockProvider->getId() === (int)$stockId) {
                continue;
            }

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
