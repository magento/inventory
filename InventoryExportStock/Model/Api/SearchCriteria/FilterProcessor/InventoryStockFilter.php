<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model\Api\SearchCriteria\FilterProcessor;

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Applies inventory stock filtering by joining MSI tables and restricting by stock_id.
 */
class InventoryStockFilter implements CustomFilterInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        private readonly ResourceConnection          $resourceConnection,
        private readonly StockConfigurationInterface $stockConfiguration
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $stockId = (int)$filter->getValue();
        $select = $collection->getSelect();

        $notAllowedProductTypes = array_keys(
            array_filter(
                $this->stockConfiguration->getIsQtyTypeIds(),
                fn($allowed) => $allowed === false
            )
        );

        $select->joinLeft(
            ['inventory_source_item' => $this->resourceConnection->getTableName('inventory_source_item')],
            'inventory_source_item.sku = e.sku',
            []
        )->joinLeft(
            ['stock_source_link' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
            'stock_source_link.source_code = inventory_source_item.source_code',
            []
        )->where(
            'stock_source_link.stock_id = ?',
            $stockId
        )->orWhere(
            'e.type_id IN (?)',
            $notAllowedProductTypes
        );

        // Prevent duplicates when a product has multiple source items in the same stock
        $select->group('e.entity_id');

        return true;
    }
}
