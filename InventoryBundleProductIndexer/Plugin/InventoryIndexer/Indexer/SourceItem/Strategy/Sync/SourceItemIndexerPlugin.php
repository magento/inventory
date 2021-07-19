<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\SourceItemIndexer as BundleProductsSourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

/**
 * Reindex bundle product source items.
 */
class SourceItemIndexerPlugin
{
    /**
     * @var BundleProductsSourceItemIndexer
     */
    private $bundleProductsSourceItemIndexer;

    /**
     * @param BundleProductsSourceItemIndexer $configurableProductsSourceItemIndexer
     */
    public function __construct(
        BundleProductsSourceItemIndexer $configurableProductsSourceItemIndexer
    ) {
        $this->bundleProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
    }

    /**
     * Reindex source items list for bundle products.
     *
     * @param Sync $subject
     * @param callable $proceed
     * @param array $sourceItemIds
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function aroundExecuteList(
        Sync $subject,
        callable $proceed,
        array $sourceItemIds
    ) {
        $proceed($sourceItemIds);
        $this->bundleProductsSourceItemIndexer->executeList($sourceItemIds);
    }
}
