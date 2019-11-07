<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

/**
 * This Observer will trigger if a stock is successfully saved and create the stock index table.
 */
class StockSaveObserver implements ObserverInterface
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
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexStructureInterface $indexStructure
     */
    public function __construct(IndexNameBuilder $indexNameBuilder, IndexStructureInterface $indexStructure)
    {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $stockId = $observer->getData('object')->getId();

        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
            $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
        }
    }
}
