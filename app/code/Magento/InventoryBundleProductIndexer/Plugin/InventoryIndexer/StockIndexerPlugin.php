<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer;

use Magento\Framework\Exception\StateException;
use Magento\InventoryBundleProductIndexer\Indexer\Stock\StockIndexer as BundleProductsStockIndexer;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;

class StockIndexerPlugin
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
     * @param StockIndexer $subject
     * @param void $result
     * @param array $stockIds
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws StateException
     */
    public function afterExecuteList(
        StockIndexer $subject,
        $result,
        array $stockIds
    ) {
        $this->bundleProductsStockIndexer->executeList($stockIds);
    }
}
