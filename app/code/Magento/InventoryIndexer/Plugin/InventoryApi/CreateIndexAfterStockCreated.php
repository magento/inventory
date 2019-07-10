<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;

/**
 * Create stock table on Save method of StockRepositoryInterface
 */
class CreateIndexAfterStockCreated
{
    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexStructureInterface $indexStructure
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        IndexStructureInterface $indexStructure,
        StockIndexer $stockIndexer
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
        $this->stockIndexer = $stockIndexer;
    }

    /**
     * Create stock table after save stock
     *
     * @param StockRepositoryInterface $subject
     * @param int $stockId
     * @param StockInterface $stock
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        StockRepositoryInterface $subject,
        int $stockId,
        StockInterface $stock
    ): int {
        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
            $this->stockIndexer->executeRow($stockId);
        }

        return $stockId;
    }
}
