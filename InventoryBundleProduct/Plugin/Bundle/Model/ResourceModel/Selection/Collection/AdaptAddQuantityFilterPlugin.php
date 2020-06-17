<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\ResourceModel\Selection\Collection;

use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt add quantity filter to bundle selection in multi stock environment plugin.
 */
class AdaptAddQuantityFilterPlugin
{
    /**
     * @var Item
     */
    private $stockItem;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @param Item $stockItem
     * @param StoreManagerInterface $storeManager
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        Item $stockItem,
        StoreManagerInterface $storeManager,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) {
        $this->stockItem = $stockItem;
        $this->storeManager = $storeManager;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * Adapt quantity filter for multi stock environment.
     *
     * @param Collection $subject
     * @param \Closure $proceed
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddQuantityFilter(
        Collection $subject,
        \Closure $proceed
    ): Collection {
        $store = $this->storeManager->getStore($subject->getStoreId());
        $stock = $this->stockByWebsiteIdResolver->execute((int)$store->getWebsiteId());
        $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stock->getStockId());
        $resource = $subject->getResource();
        $subject->getSelect()->join(
            ['product_entity' => $resource->getTable('catalog_product_entity')],
            sprintf('product_entity.entity_id = %s.entity_id', Collection::MAIN_TABLE_ALIAS),
            []
        );
        $isSalableColumnName = IndexStructure::IS_SALABLE;
        $subject->getSelect()
            ->join(
                ['inventory_stock' => $stockIndexTableName],
                'product_entity.sku = inventory_stock.' . IndexStructure::SKU,
                [$isSalableColumnName]
            );
        $manageStockExpr = $this->stockItem->getManageStockExpr('stock_item');
        $backordersExpr = $this->stockItem->getBackordersExpr('stock_item');
        $minQtyExpr = $subject->getConnection()->getCheckSql(
            'selection.selection_can_change_qty',
            $this->stockItem->getMinSaleQtyExpr('stock_item'),
            'selection.selection_qty'
        );
        $where = $manageStockExpr . ' = 0';
        $where .= ' OR ('
            . 'inventory_stock.is_salable = ' . Stock::STOCK_IN_STOCK
            . ' AND ('
            . $backordersExpr . ' != ' . Stock::BACKORDERS_NO
            . ' OR '
            . $minQtyExpr . ' <= inventory_stock.quantity'
            . ')'
            . ')';
        $subject->getSelect()
            ->joinInner(
                ['stock_item' => $this->stockItem->getMainTable()],
                'selection.product_id = stock_item.product_id',
                []
            )
            ->where($where);

        return $subject;
    }
}
