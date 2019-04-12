<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Get salable quantity data
 */
class GetSalableQuantityData
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * GetSalableQuantityData constructor.
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) {
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * @param int $stockId
     * @param array $skus
     * @return array
     */
    public function execute(int $stockId, array $skus): array
    {
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $this->resource->getConnection();
        $stockItemTableName = $this->stockIndexTableNameResolver->execute($stockId);

        $catalogInventoryTable = $connection->getTableName('cataloginventory_stock_item');
        $productTable = $connection->getTableName('catalog_product_entity');
        $inventoryReservationTable = $connection->getTableName('inventory_reservation');

        $subSelect = $connection->select()
            ->from(
                ['ir' => $inventoryReservationTable],
                [
                    'SUM(ir.quantity) AS quantity',
                    'ir.sku'
                ]
            )
            ->joinInner(
                ['inventory_stock' => $stockItemTableName],
                'inventory_stock.sku = ir.sku AND ir.stock_id = ' . $stockId,
                []
            )
            ->group('ir.sku');

        $salableQuantityExpression = '(main_table.quantity + IF(t.quantity IS NULL, 0, t.quantity)';
        $salableQuantityExpression .= ' - catalog_inventory.min_qty) as salable_quantity';

        $select = $connection->select()
            ->from(
                ['main_table' => $stockItemTableName],
                ['sku', 'is_salable']
            )
            ->joinLeft(
                ['t' => $subSelect],
                't.sku = main_table.sku',
                $salableQuantityExpression
            )
            ->joinLeft(
                ['product_entity' => $productTable],
                'product_entity.sku = main_table.sku',
                []
            )
            ->joinLeft(
                ['catalog_inventory' => $catalogInventoryTable],
                'catalog_inventory.product_id = product_entity.entity_id',
                []
            )
            ->where('main_table.sku in (?)', $skus)
            ->where('main_table.is_salable = ?', 1);

        if ($connection->isTableExists($stockItemTableName)) {
            return $connection->fetchAll($select) ?: null;
        }

        return null;
    }
}
