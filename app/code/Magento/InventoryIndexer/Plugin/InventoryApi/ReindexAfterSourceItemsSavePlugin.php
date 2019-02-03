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
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\Indexer\Source\SourceItemProcessor;

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
     * @var $sourceItemProcessor
     */
    private $sourceItemProcessor;

    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param SourceItemProcessor $sourceItemProcessor
     */
    public function __construct(
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        SourceItemProcessor $sourceItemProcessor
    ) {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->sourceItemProcessor = $sourceItemProcessor;
    }

    /**
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
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        if (count($sourceItemIds) && !$this->sourceItemProcessor->isIndexerScheduled()) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
            return;
        }
        $this->sourceItemProcessor->markIndexerAsInvalid();
    }
}
