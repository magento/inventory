<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer;

use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\SourceItemIndexer
    as BundleProductsSourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

class SourceItemIndexerPlugin
{
    /**
     * @var BundleProductsSourceItemIndexer
     */
    private $bundleProductsSourceItemIndexer;

    /**
     * @param BundleProductsSourceItemIndexer $bundleProductsSourceItemIndexer
     */
    public function __construct(
        BundleProductsSourceItemIndexer $bundleProductsSourceItemIndexer
    ) {
        $this->bundleProductsSourceItemIndexer = $bundleProductsSourceItemIndexer;
    }

    /**
     * @param SourceItemIndexer $subject
     * @param void $result
     * @param array $sourceItemIds
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function afterExecuteList(
        SourceItemIndexer $subject,
        $result,
        array $sourceItemIds
    ) {
        $this->bundleProductsSourceItemIndexer->executeList($sourceItemIds);
    }
}
