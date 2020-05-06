<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

/**
 * Source Item indexer
 *
 * @api
 */
class SourceItemIndexer
{
    /**
     * @var SourceItemReindexStrategy
     */
    private $sourceItemReindexStrategy;

    /**
     * @param SourceItemReindexStrategy $sourceItemReindexStrategy
     */
    public function __construct(
        SourceItemReindexStrategy $sourceItemReindexStrategy
    ) {
        $this->sourceItemReindexStrategy = $sourceItemReindexStrategy;
    }

    /**
     * @return void
     */
    public function executeFull()
    {
        $this->sourceItemReindexStrategy->executeFull();
    }

    /**
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId)
    {
        $this->sourceItemReindexStrategy->executeRow($sourceItemId);
    }

    /**
     * @param array $sourceItemIds
     * @return void
     */
    public function executeList(array $sourceItemIds)
    {
        $this->sourceItemReindexStrategy->executeList($sourceItemIds);
    }
}
