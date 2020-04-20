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
use Magento\InventoryIndexer\Indexer\IndexScheduler;

/**
 * Plugin for reindex after source items save
 */
class ReindexAfterSourceItemsSavePlugin
{
    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var IndexScheduler
     */
    private $indexScheduler;

    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param IndexScheduler $indexScheduler
     */
    public function __construct(GetSourceItemIds $getSourceItemIds, IndexScheduler $indexScheduler)
    {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->indexScheduler = $indexScheduler;
    }

    /**
     * Method after execution of save source items
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
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        if (count($sourceItemIds)) {
            $this->indexScheduler->scheduleSourceItems($sourceItemIds);
        }
    }
}
