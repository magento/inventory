<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use ArrayIterator;

/**
 * Configurable product source item indexer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Will be removed after deleting DefaultStockProviderInterface
 */
class SourceItemIndexer
{
    /**
     * Default batch size
     */
    private const BATCH_SIZE = 100;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexHandlerInterface
     */
    private $indexHandler;

    /**
     * @var IndexDataBySkuListProvider
     */
    private $indexDataBySkuListProvider;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var SiblingSkuListInStockProvider
     */
    private $siblingSkuListInStockProvider;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

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
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexHandlerInterface $indexHandler
     * @param IndexStructureInterface $indexStructure
     * @param IndexDataBySkuListProvider $indexDataBySkuListProvider
     * @param SiblingSkuListInStockProvider $siblingSkuListInStockProvider
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param Batch|null $batch
     * @param int|null $batchSize
     * @param DeploymentConfig|null $deploymentConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexHandlerInterface $indexHandler,
        IndexStructureInterface $indexStructure,
        IndexDataBySkuListProvider $indexDataBySkuListProvider,
        SiblingSkuListInStockProvider $siblingSkuListInStockProvider,
        DefaultStockProviderInterface $defaultStockProvider,
        ?Batch $batch = null,
        ?int $batchSize = null,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexHandler = $indexHandler;
        $this->indexDataBySkuListProvider = $indexDataBySkuListProvider;
        $this->indexStructure = $indexStructure;
        $this->siblingSkuListInStockProvider = $siblingSkuListInStockProvider;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->batch = $batch ?: ObjectManager::getInstance()->get(Batch::class);
        $this->batchSize = $batchSize ?? self::BATCH_SIZE;
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Executes index by list of stock ids
     *
     * @param array $sourceItemIds
     */
    public function executeList(array $sourceItemIds)
    {
        $skuListInStockList = $this->siblingSkuListInStockProvider->execute($sourceItemIds);
        $this->batchSize = $this->deploymentConfig->get(
            self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . InventoryIndexer::INDEXER_ID . '/' . 'configurable'
        ) ??
            $this->deploymentConfig->get(
                self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . InventoryIndexer::INDEXER_ID . '/' . 'default'
            )
                ?? $this->batchSize;

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

            $indexData = $this->indexDataBySkuListProvider->execute($stockId, $skuList);

            foreach ($this->batch->getItems($indexData, $this->batchSize) as $batchData) {
                $batchIndexData = new ArrayIterator($batchData);
                $this->indexHandler->cleanIndex(
                    $mainIndexName,
                    $batchIndexData,
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
