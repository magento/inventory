<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexerInterface;

/**
 * Receiver of currently active reindex strategy
 *
 * @api
 */
interface SourceItemReindexStrategyInterface
{
    /**
     * Get currently enabled strategy
     *
     * @return SourceItemIndexerInterface
     */
    public function getStrategy(): SourceItemIndexerInterface;
}
