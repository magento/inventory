<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\SiblingProductsProviderInterface;
use Magento\InventoryIndexer\Indexer\Stock\GetAllStockIds;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

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
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism.
     *
     * @param GetAllStockIds $getAllStockIds
     * @param IndexStructureInterface $indexStructure
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param SiblingProductsProviderInterface $productsProvider
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        IndexStructureInterface $indexStructure,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        DefaultStockProviderInterface $defaultStockProvider,
        private readonly SiblingProductsProviderInterface $productsProvider,
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->indexStructure = $indexStructure;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->defaultStockProvider = $defaultStockProvider;
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

            $data = $this->productsProvider->getData($mainIndexName, $skuList);
            $this->indexHandler->cleanIndex(
                $mainIndexName,
                new ArrayIterator($skuList),
                ResourceConnection::DEFAULT_CONNECTION
            );

            $this->indexHandler->saveIndex(
                $mainIndexName,
                new ArrayIterator($data),
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
    }
}
