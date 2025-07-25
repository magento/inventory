<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Reindex after decrement quantity of source items plugin
 */
class ReindexAfterDecrementSourceItemQty
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
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     */
    public function __construct(GetSourceItemIds $getSourceItemIds, SourceItemIndexer $sourceItemIndexer)
    {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
    }

    /**
     * Reindexes source items after decrementing their quantity by retrieving IDs and executing the indexer.
     *
     * @param DecrementSourceItemQty $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItemDecrementData
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        DecrementSourceItemQty $subject,
        $result,
        array $sourceItemDecrementData
    ) {
        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        if (count($sourceItemIds)) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
    }
}
