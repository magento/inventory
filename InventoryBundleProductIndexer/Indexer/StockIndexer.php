<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\IndexDataBySkuListProvider;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\GetAllStockIds;
use Magento\InventoryIndexer\Indexer\Stock\PrepareIndexDataForClearingIndex;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexTableSwitcherInterface;

/**
 * Index bundle products for given stocks.
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
     * @var IndexDataBySkuListProvider
     */
    private $indexDataBySkuListProvider;

    /**
     * @var IndexTableSwitcherInterface
     */
    private $indexTableSwitcher;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var PrepareIndexDataForClearingIndex
     */
    private $prepareIndexDataForClearingIndex;

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism.
     *
     * @param GetAllStockIds $getAllStockIds
     * @param IndexStructureInterface $indexStructure
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexDataBySkuListProvider $indexDataBySkuListProvider
     * @param IndexTableSwitcherInterface $indexTableSwitcher
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param PrepareIndexDataForClearingIndex $prepareIndexDataForClearingIndex
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        IndexStructureInterface $indexStructure,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        IndexDataBySkuListProvider $indexDataBySkuListProvider,
        IndexTableSwitcherInterface $indexTableSwitcher,
        DefaultStockProviderInterface $defaultStockProvider,
        PrepareIndexDataForClearingIndex $prepareIndexDataForClearingIndex
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->indexStructure = $indexStructure;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexDataBySkuListProvider = $indexDataBySkuListProvider;
        $this->indexTableSwitcher = $indexTableSwitcher;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->prepareIndexDataForClearingIndex = $prepareIndexDataForClearingIndex;
    }

    /**
     * Index bundle products for all stocks.
     *
     * @return void
     * @throws StateException
     */
    public function executeFull()
    {
        $stockIds = $this->getAllStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * Index bundle products for given stock.
     *
     * @param int $stockId
     * @param array $skuList
     * @return void
     * @throws StateException
     */
    public function executeRow(int $stockId, array $skuList = [])
    {
        $this->executeList([$stockId], $skuList);
    }

    /**
     * Index bundle products for given stocks.
     *
     * @param array $stockIds
     * @param array $skuList
     * @return void
     * @throws StateException
     */
    public function executeList(array $stockIds, array $skuList = [])
    {
        foreach ($stockIds as $stockId) {
            if ($this->defaultStockProvider->getId() === $stockId) {
                continue;
            }

            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }

            $indexData = $this->indexDataBySkuListProvider->execute($stockId, $skuList);

            $this->indexHandler->cleanIndex(
                $mainIndexName,
                $this->prepareIndexDataForClearingIndex->execute($indexData),
                ResourceConnection::DEFAULT_CONNECTION
            );

            $this->indexHandler->saveIndex(
                $mainIndexName,
                $indexData,
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
    }
}
