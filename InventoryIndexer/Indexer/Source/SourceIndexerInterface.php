<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Source;

/**
 * Interface for Inventory indexers
 *
 * @api
 */
interface SourceIndexerInterface
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
     * @param int $sourceCode
     * @return void
     */
    public function executeRow(string $sourceCode): void;

    /**
     * Execute list of ids
     *
     * @param array $sourceCodes
     * @return void
     */
    public function executeList(array $sourceCodes): void;
}
