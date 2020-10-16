<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Adapt adding is in stock filter to collection for Multi Stocks.
 */
class AddIsInStockFilterToCollection
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableProvider;
    /**
     * @var StockStatusFilter
     */
    private $stockStatusFilter;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableProvider
     * @param StockStatusFilter $stockStatusFilter
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableProvider,
        StockStatusFilter $stockStatusFilter
    ) {
        $this->stockIndexTableProvider = $stockIndexTableProvider;
        $this->stockStatusFilter = $stockStatusFilter;
    }

    /**
     * Modify "is in stock" collection filter to support non-default stocks.
     *
     * @param Collection $collection
     * @param int $stockId
     * @return void
     */
    public function execute($collection, int $stockId)
    {
        $this->stockStatusFilter->execute(
            $collection->getSelect(),
            'e',
            'stock_status_index',
            $stockId
        );
    }
}
