<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Source;

/**
 * Receiver of currently active reindex strategy
 *
 * @api
 */
interface SourceReindexStrategyInterface
{
    /**
     * Get currently enabled strategy
     *
     * @return SourceIndexerInterface
     */
    public function getStrategy(): SourceIndexerInterface;
}
