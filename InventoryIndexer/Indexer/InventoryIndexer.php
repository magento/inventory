<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\Indexer\ActionInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Represents the Inventory Indexer, responsible for handling full, single, or multiple source item indexing operations.
 *
 * @api
 */
class InventoryIndexer implements ActionInterface
{
    /**
     * Indexer ID in configuration
     */
    public const INDEXER_ID = 'inventory';

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @param SourceItemIndexer $sourceItemIndexer
     */
    public function __construct(
        SourceItemIndexer $sourceItemIndexer
    ) {
        $this->sourceItemIndexer = $sourceItemIndexer;
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $this->sourceItemIndexer->executeFull();
    }

    /**
     * @inheritdoc
     */
    public function executeRow($sourceItemId)
    {
        $this->sourceItemIndexer->executeList([$sourceItemId]);
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $sourceItemIds)
    {
        $this->sourceItemIndexer->executeList($sourceItemIds);
    }
}
