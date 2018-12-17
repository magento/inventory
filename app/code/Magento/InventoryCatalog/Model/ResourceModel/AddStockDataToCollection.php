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
 * Add Stock data to collection
 */
class AddStockDataToCollection
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(StockIndexTableNameResolverInterface $stockIndexTableNameResolver)
    {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * @param Collection $collection
     * @param bool $isFilterInStock
     * @param int $stockId
     * @return void
     */
    public function execute(Collection $collection, bool $isFilterInStock, int $stockId)
    {
        $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stockId);

        $collection->getSelect()
            ->join(
                ['stock_status_index' => $stockIndexTableName],
                sprintf('%s.entity_id = stock_status_index.product_id', Collection::MAIN_TABLE_ALIAS),
                [IndexStructure::IS_SALABLE]
            );

        if ($isFilterInStock) {
            $collection->getSelect()
                ->where('stock_status_index.' . IndexStructure::IS_SALABLE . ' = ?', 1);
        }
    }
}
