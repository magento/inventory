<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Generic in-stock status filter for multi stocks inventory
 */
class StockStatusFilter
{
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
     * Add in-stock status constraint to the select for non default stock
     *
     * @param Select $select
     * @param string $productTableAlias
     * @param string $stockStatusTableAlias
     * @param int $stockId
     * @return Select
     */
    public function execute(
        Select $select,
        string $productTableAlias,
        string $stockStatusTableAlias,
        int $stockId
    ): Select {
        $stockStatusTable = $this->stockIndexTableProvider->execute($stockId);
        $isSalableFieldName = IndexStructure::IS_SALABLE;
        $select->join(
            [$stockStatusTableAlias => $stockStatusTable],
            "{$stockStatusTableAlias}.sku = {$productTableAlias}.sku",
            []
        );
        $select->where("{$stockStatusTableAlias}.{$isSalableFieldName} = ?", 1);
        return $select;
    }
}
