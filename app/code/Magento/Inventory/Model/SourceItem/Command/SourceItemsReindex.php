<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;

/**
 * Wrapper for SourceItems Indexer.
 * In future, this class will provide some logic related to `update by schedule` mode.
 */
class SourceItemsReindex
{
    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(IndexerInterfaceFactory $indexerFactory)
    {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * @param array $sourceItemIds
     *
     * @return void
     */
    public function execute(array $sourceItemIds)
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->indexerFactory->create();
        $indexer->load(SourceItemIndexer::INDEXER_ID);
        $indexer->reindexList($sourceItemIds);
    }
}
