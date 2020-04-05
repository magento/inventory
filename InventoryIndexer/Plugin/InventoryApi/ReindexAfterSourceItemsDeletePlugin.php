<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemsWithId;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Reindex after source items delete plugin
 */
class ReindexAfterSourceItemsDeletePlugin
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
     * @var GetSourceItemsWithId
     */
    private $getSourceItemsWithId;

    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param GetSourcesItemById $getSourceItemsWithId
     */
    public function __construct(GetSourceItemIds $getSourceItemIds, SourceItemIndexer $sourceItemIndexer, GetSourceItemsWithId $getSourceItemsWithId)
    {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->getSourceItemsWithId = $getSourceItemsWithId;
    }

    /**
     * @param SourceItemsDeleteInterface $subject
     * @param callable $proceed
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SourceItemsDeleteInterface $subject,
        callable $proceed,
        array $sourceItems
    ) {

        // We need to double-pump the source item list, as we will end up with skus that still have some sources, and some that have none
        $sourceItemsBefore = $this->getSourceItemsWithId->execute($sourceItems);

        $proceed($sourceItems);

        // Get back the list of items we still have other sources for.
        $sourceItemIdsAfter = $this->getSourceItemIds->execute($sourceItems);

        if (count($sourceItemIdsAfter)) {
            $this->sourceItemIndexer->executeList($sourceItemIdsAfter);
        }

        // Double check to see how many source items were removed and don't exist anywhere else
        $sourceItemsOrphaned = array_diff(array_keys($sourceItemsBefore), $sourceItemIdsAfter);

        if (count($sourceItemsOrphaned)) {

            $sourceCodeSkuMap = [];
            foreach ($sourceItemsOrphaned as $orphanedSourceItemId) {
                $orphanedSourceItem = $sourceItemsBefore[$orphanedSourceItemId];
                $sourceCode = $orphanedSourceItem[SourceItemInterface::SOURCE_CODE];
                if (false == isset($sourceCodeSkuMap[$sourceCode])) {
                    $sourceCodeSkuMap[$sourceCode] = [];
                }
                $sourceCodeSkuMap[$sourceCode][] = $orphanedSourceItem[SourceItemInterface::SKU];
            }

            $this->sourceItemIndexer->executeOrphanList($sourceCodeSkuMap);
        }
    }
}
