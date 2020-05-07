<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\Exception\LocalizedException;

/**
 * Index source items service.
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
     * Reindex all source items.
     *
     * @return void
     * @throws LocalizedException
     */
    public function executeFull()
    {
        $this->sourceItemReindexStrategy->executeFull();
    }

    /**
     * Reindex given source item.
     *
     * @param int $sourceItemId
     * @return void
     * @throws LocalizedException
     */
    public function executeRow(int $sourceItemId)
    {
        $this->sourceItemReindexStrategy->executeRow($sourceItemId);
    }

    /**
     * Reindex given source items.
     *
     * @param array $sourceItemIds
     * @return void
     * @throws LocalizedException
     */
    public function executeList(array $sourceItemIds)
    {
        $this->sourceItemReindexStrategy->executeList($sourceItemIds);
    }
}
