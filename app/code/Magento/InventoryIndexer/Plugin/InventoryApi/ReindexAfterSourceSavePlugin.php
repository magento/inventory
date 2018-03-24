<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

/**
 * Reindex after source save plugin
 */
class ReindexAfterSourceSavePlugin
{
    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * @param SourceIndexer $sourceIndexer
     */
    public function __construct(SourceIndexer $sourceIndexer)
    {
        $this->sourceIndexer = $sourceIndexer;
    }

    /**
     * @param SourceRepositoryInterface $subject
     * @param void $result
     * @param SourceInterface $source
     * @return void
     */
    public function afterSave(
        SourceRepositoryInterface $subject,
        $result,
        SourceInterface $source
    ) {
        $this->sourceIndexer->executeRow($source->getSourceCode());
    }
}
