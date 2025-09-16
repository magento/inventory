<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Invalidate inventory index on global inventory configuration changes
 */
class UpdateSourceItemsUponConfigChangeObserver implements ObserverInterface
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Invalidates the inventory index if it is valid, triggered by global inventory configuration changes.
     *
     * @param EventObserver $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventObserver $observer)
    {
        $indexer = $this->indexerRegistry->get(InventoryIndexer::INDEXER_ID);
        if ($indexer->isValid()) {
            $indexer->invalidate();
        }
    }
}
