<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer\Indexer\Stock\Strategy\Sync;

use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer as BundleProductsStockIndexer;
use Magento\InventoryIndexer\Indexer\Stock\Strategy\Sync;

/**
 * Index bundle products for stocks plugin.
 */
class ReindexFullPlugin
{
    /**
     * @var BundleProductsStockIndexer
     */
    private $bundleProductsStockIndexer;

    /**
     * @param BundleProductsStockIndexer $bundleProductsStockIndexer
     */
    public function __construct(
        BundleProductsStockIndexer $bundleProductsStockIndexer
    ) {
        $this->bundleProductsStockIndexer = $bundleProductsStockIndexer;
    }

    /**
     * Index bundle products for stocks.
     *
     * @param Sync $subject
     * @param void $result
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function afterExecuteFull(
        Sync $subject,
        $result
    ): void {
        $this->bundleProductsStockIndexer->executeFull();
    }
}
