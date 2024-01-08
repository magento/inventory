<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\SourceItemIndexer as BundleProductsSourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\CompositeProductProcessorInterface;

/**
 * Reindex bundle product source items.
 */
class SourceItemIndexerProcessor implements CompositeProductProcessorInterface
{
    /**
     * @var BundleProductsSourceItemIndexer
     */
    private $bundleProductsSourceItemIndexer;

    /**
     * Processor sort order
     *
     * @var int
     */
    private $sortOrder;

    /**
     * @param BundleProductsSourceItemIndexer $configurableProductsSourceItemIndexer
     * @param array $sortOrder
     */
    public function __construct(
        BundleProductsSourceItemIndexer $configurableProductsSourceItemIndexer,
        int $sortOrder = 5
    ) {
        $this->bundleProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Reindex source items list for bundle products.
     *
     * @param array $sourceItemIds
     * @param array $saleableStatusesBeforeSync
     * @param array $saleableStatusesAfterSync
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function process(
        array $sourceItemIds,
        array $saleableStatusesBeforeSync,
        array $saleableStatusesAfterSync
    ): void {
        $this->bundleProductsSourceItemIndexer->executeList($sourceItemIds);
    }

    /**
     * @inheritdoc
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
