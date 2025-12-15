<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

interface CompositeProductProcessorInterface
{
    /**
     * Return the processor sort order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Process product list with saleability changes
     *
     * @param array $saleableStatusesBeforeSync
     * @param array $saleableStatusesAfterSync
     * @return void
     */
    public function process(
        array $saleableStatusesBeforeSync,
        array $saleableStatusesAfterSync
    ): void;
}
