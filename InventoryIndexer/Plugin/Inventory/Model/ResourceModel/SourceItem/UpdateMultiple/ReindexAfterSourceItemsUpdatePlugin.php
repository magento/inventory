<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\Inventory\Model\ResourceModel\SourceItem\UpdateMultiple;

use Magento\Inventory\Model\ResourceModel\SourceItem\UpdateMultiple;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetSourceItemIdsBySku;

/**
 * Reindex after source items update plugin.
 */
class ReindexAfterSourceItemsUpdatePlugin
{
    /**
     * @var GetSourceItemIdsBySku
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @param GetSourceItemIdsBySku $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     */
    public function __construct(GetSourceItemIdsBySku $getSourceItemIds, SourceItemIndexer $sourceItemIndexer)
    {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
    }

    /**
     * Reindex source items after source items update.
     *
     * @param UpdateMultiple $subject
     * @param void $result
     * @param string $oldSku
     * @param string $newSku
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(UpdateMultiple $subject, $result, string $oldSku, string $newSku): void
    {
        $sourceItemIds = $this->getSourceItemIds->execute($newSku);
        if (count($sourceItemIds)) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
    }
}
