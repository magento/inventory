<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

/**
 * CReindex default stock during installation
 */
class ReindexDefaultStock
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockIndexer $stockIndexer
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexStructureInterface $indexStructure
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        StockIndexer $stockIndexer,
        IndexNameBuilder $indexNameBuilder,
        IndexStructureInterface $indexStructure
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockIndexer = $stockIndexer;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
    }

    /**
     * Create default stock.
     *
     * @return void
     * @throws StateException
     */
    public function execute()
    {
        $stockId = $this->defaultStockProvider->getId();
        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
            $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
        }
        $this->stockIndexer->executeRow($this->defaultStockProvider->getId());
    }
}
