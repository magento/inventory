<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\Indexer\AbstractProcessor;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

class Processor extends AbstractProcessor
{
    /**
     * Indexer ID
     */
    public const INDEXER_ID = InventoryIndexer::INDEXER_ID;
}
