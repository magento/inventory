<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProductIndexer\Plugin\InventoryIndexer\Indexer\Stock\Strategy\Sync;

use Magento\Framework\Exception\StateException;
use Magento\InventoryGroupedProductIndexer\Indexer\Stock\StockIndexer as GroupedProductStockIndexer;
use Magento\InventoryIndexer\Indexer\Stock\Strategy\Sync;

/**
 * Reindex grouped product.
 */
class ReindexFullPlugin
{
    /**
     * @var GroupedProductStockIndexer
     */
    private $groupedProductStockIndexer;

    /**
     * @param GroupedProductStockIndexer $groupedProductStockIndexer
     */
    public function __construct(
        GroupedProductStockIndexer $groupedProductStockIndexer
    ) {
        $this->groupedProductStockIndexer = $groupedProductStockIndexer;
    }

    /**
     * Reindex grouped product.
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
        $this->groupedProductStockIndexer->executeFull();
    }
}
