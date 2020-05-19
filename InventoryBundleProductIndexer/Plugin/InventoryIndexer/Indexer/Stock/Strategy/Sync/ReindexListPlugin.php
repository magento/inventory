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
 * Index bundle products for given stocks plugin.
 */
class ReindexListPlugin
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
     * Index bundle products for given stocks.
     *
     * @param Sync $subject
     * @param void $result
     * @param array $stockIds
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function afterExecuteList(
        Sync $subject,
        $result,
        array $stockIds
    ): void {
        $this->bundleProductsStockIndexer->executeList($stockIds);
    }
}
