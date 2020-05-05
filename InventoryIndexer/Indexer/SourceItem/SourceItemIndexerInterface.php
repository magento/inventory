<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

/**
 * Interface for Inventory indexers
 *
 * @api
 */
interface SourceItemIndexerInterface
{
    /**
     * Full reindex
     *
     * @return void
     */
    public function executeFull(): void;

    /**
     * Execute one single id
     *
     * @param int $sourceItemId
     * @return void
     */
    public function executeRow(int $sourceItemId): void;

    /**
     * Execute list of ids
     *
     * @param array $sourceItemIds
     * @return void
     */
    public function executeList(array $sourceItemIds): void;
}
