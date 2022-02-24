<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\Processor;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Reindex after source items save plugin
 */
class ReindexAfterSourceItemsSavePlugin
{
    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var Processor
     */
    private $sourceItemIndexerProcessor;

    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param Processor $sourceItemIndexerProcessor
     */
    public function __construct(
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        Processor $sourceItemIndexerProcessor
    ) {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->sourceItemIndexerProcessor = $sourceItemIndexerProcessor;
    }

    /**
     * Execute source item indexer after saving source items
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ) {
        if (!$this->sourceItemIndexerProcessor->getIndexer()->isScheduled()) {
            $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
            if (count($sourceItemIds)) {
                $this->sourceItemIndexer->executeList($sourceItemIds);
            }
        }
    }
}
