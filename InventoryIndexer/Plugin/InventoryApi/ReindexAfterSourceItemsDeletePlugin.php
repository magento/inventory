<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

/**
 * Handles reindexing of source items after they are deleted by intercepting the execution
 * and triggering the source indexer.
 */
class ReindexAfterSourceItemsDeletePlugin
{
    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * @param SourceIndexer $sourceIndexer
     */
    public function __construct(SourceIndexer $sourceIndexer)
    {
        $this->sourceIndexer = $sourceIndexer;
    }

    /**
     * Reindexes source items after deletion by intercepting execution and triggering the source indexer.
     *
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
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[] = $sourceItem->getSourceCode();
        }

        $proceed($sourceItems);

        if (count($sourceCodes)) {
            $this->sourceIndexer->executeList($sourceCodes);
        }
    }
}
