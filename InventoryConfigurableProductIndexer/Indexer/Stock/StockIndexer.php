<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer\Stock;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\GetAllStockIds;
use Magento\InventoryIndexer\Indexer\Stock\PrepareIndexDataForClearingIndex;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexTableSwitcherInterface;
use ArrayIterator;

/**
 * Configurable product stock indexer class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Will be removed after deleting DefaultStockProviderInterface
 */
class StockIndexer
{
    /**
     * Default batch size
     */
    private const BATCH_SIZE = 100;

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
     * @var IndexDataByStockIdProvider
     */
    private $indexDataByStockIdProvider;

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
     * @var int
     */
    private $batchSize;

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var DeploymentConfig|null
     */
    private $deploymentConfig;

    /**
     * Deployment config path
     *
     * @var string
     */
    private const DEPLOYMENT_CONFIG_INDEXER_BATCHES = 'indexer/batch_size/';

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism
     *
     * @param GetAllStockIds $getAllStockIds
     * @param IndexStructureInterface $indexStructure
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexDataByStockIdProvider $indexDataByStockIdProvider
     * @param IndexTableSwitcherInterface $indexTableSwitcher
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param PrepareIndexDataForClearingIndex|null $prepareIndexDataForClearingIndex
     * @param Batch|null $batch
     * @param int|null $batchSize
     * @param DeploymentConfig|null $deploymentConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) All parameters are needed for backward compatibility
     */
    public function __construct(
        GetAllStockIds $getAllStockIds,
        IndexStructureInterface $indexStructure,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        IndexDataByStockIdProvider $indexDataByStockIdProvider,
        IndexTableSwitcherInterface $indexTableSwitcher,
        DefaultStockProviderInterface $defaultStockProvider,
        ?PrepareIndexDataForClearingIndex $prepareIndexDataForClearingIndex = null,
        ?Batch $batch = null,
        ?int $batchSize = null,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->getAllStockIds = $getAllStockIds;
        $this->indexStructure = $indexStructure;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexDataByStockIdProvider = $indexDataByStockIdProvider;
        $this->indexTableSwitcher = $indexTableSwitcher;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->prepareIndexDataForClearingIndex = $prepareIndexDataForClearingIndex ?: ObjectManager::getInstance()
            ->get(PrepareIndexDataForClearingIndex::class);
        $this->batch = $batch ?: ObjectManager::getInstance()->get(Batch::class);
        $this->batchSize = $batchSize ?? self::BATCH_SIZE;
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Executes full index
     *
     * @return void
     * @throws StateException
     */
    public function executeFull(): void
    {
        $stockIds = $this->getAllStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * Executes row index by stock Id
     *
     * @param int $stockId
     * @return void
     * @throws StateException
     */
    public function executeRow(int $stockId): void
    {
        $this->executeList([$stockId]);
    }

    /**
     * Executes index by list of stock ids
     *
     * @param array $stockIds
     * @return void
     * @throws StateException
     */
    public function executeList(array $stockIds): void
    {
        $this->batchSize = $this->deploymentConfig->get(
            self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . InventoryIndexer::INDEXER_ID . '/' . 'configurable'
        ) ??
            $this->deploymentConfig->get(
                self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . InventoryIndexer::INDEXER_ID . '/' . 'default'
            )
            ?? $this->batchSize;

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

            $indexData = $this->indexDataByStockIdProvider->execute((int)$stockId);

            foreach ($this->batch->getItems($indexData, $this->batchSize) as $batchData) {
                $batchIndexData = new ArrayIterator($batchData);
                $this->indexHandler->cleanIndex(
                    $mainIndexName,
                    $this->prepareIndexDataForClearingIndex->execute($batchIndexData),
                    ResourceConnection::DEFAULT_CONNECTION
                );

                $this->indexHandler->saveIndex(
                    $mainIndexName,
                    $batchIndexData,
                    ResourceConnection::DEFAULT_CONNECTION
                );
            }
        }
    }
}
