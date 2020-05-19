<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Framework\Exception\StateException;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

/**
 * Reindex configurable source items.
 */
class SourceItemIndexerPlugin
{
    /**
     * @var SourceItemIndexer
     */
    private $configurableProductsSourceItemIndexer;

    /**
     * @param SourceItemIndexer $configurableProductsSourceItemIndexer
     */
    public function __construct(
        SourceItemIndexer $configurableProductsSourceItemIndexer
    ) {
        $this->configurableProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
    }

    /**
     * Reindex configurable product.
     *
     * @param Sync $subject
     * @param void $result
     * @param array $sourceItemIds
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function afterExecuteList(
        Sync $subject,
        $result,
        array $sourceItemIds
    ) {
        $this->configurableProductsSourceItemIndexer->executeList($sourceItemIds);
    }
}
