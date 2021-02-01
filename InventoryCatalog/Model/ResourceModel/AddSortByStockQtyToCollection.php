<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Add sort by stock quantity to product collection
 */
class AddSortByStockQtyToCollection
{
    private const SORT_TABLE_ALIAS = 'stock_qty_sort';

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableProvider;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableProvider
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableProvider
    ) {
        $this->stockIndexTableProvider = $stockIndexTableProvider;
    }

    /**
     * Add sort by stock quantity to product collection
     *
     * @param Collection $collection
     * @param string $direction
     * @param int $stockId
     */
    public function execute(
        Collection $collection,
        string $direction,
        int $stockId
    ): void {
        $stockIndexTable = $this->stockIndexTableProvider->execute($stockId);
        $stockIndexTableAlias = self::SORT_TABLE_ALIAS;
        $productTableAlias = Collection::MAIN_TABLE_ALIAS;
        $qtyFieldName = IndexStructure::QUANTITY;
        $collection->getSelect()
            ->joinLeft(
                [$stockIndexTableAlias => $stockIndexTable],
                "{$stockIndexTableAlias}.sku = {$productTableAlias}.sku",
                []
            )
            ->order("{$stockIndexTableAlias}.{$qtyFieldName} {$direction}");
    }
}
