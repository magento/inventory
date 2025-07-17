<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

class GetSalableStatuses
{
    /**
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        private readonly AreProductsSalableInterface $areProductsSalable
    ) {
    }

    /**
     * Get salable statuses for products based on skus per stock list
     *
     * @param SkuListInStock[] $skuListInStockList
     * @return array
     */
    public function execute(array $skuListInStockList) : array
    {
        $result = [];
        foreach ($skuListInStockList as $skuListInStock) {
            $stockId = $skuListInStock->getStockId();
            $skuList = $skuListInStock->getSkuList();
            $salableStatusList = $this->areProductsSalable->execute($skuList, $stockId);
            foreach ($salableStatusList as $salableStatusItem) {
                $result[$salableStatusItem->getSku()][$stockId] = $salableStatusItem->isSalable();
            }
        }
        return $result;
    }
}
