<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesAdminUi\Model\GetStockSourceLinksBySourceCode;

/**
 * Get salable quantity data
 */
class GetSalableQuantityData
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var GetStockSourceLinksBySourceCode
     */
    private $getStockSourceLinksBySourceCode;

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
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetStockSourceLinksBySourceCode $getStockSourceLinksBySourceCode
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetStockSourceLinksBySourceCode $getStockSourceLinksBySourceCode,
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getStockSourceLinksBySourceCode = $getStockSourceLinksBySourceCode;
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * @param string $stockId
     * @param array $skus
     * @return array
     * @throws LocalizedException
     */
    public function execute(int $stockId, array $skus): array
    {
        $stockItemTableName = $this->stockIndexTableNameResolver->execute($stockId);

        $subSelect = $this->resource->getConnection()->select()
            ->from(
                ['ir' => 'inventory_reservation'],
                [
                    new \Zend_Db_Expr('SUM(ir.quantity) AS quantity'),
                    'ir.sku'
                ]
            )
            ->joinInner(
                ['inventory_stock' => $stockItemTableName],
                new \Zend_Db_Expr('inventory_stock.sku = ir.sku AND ir.stock_id = ' . (int) $stockId),
                []
            )
            ->group('ir.sku');

        $salableQuantityExpression = new \Zend_Db_Expr(
            '(main_table.quantity + IF(t.quantity IS NULL, 0, t.quantity)) as salable_quantity'
        );

        $select = $this->resource->getConnection()->select()
            ->from(
                ['main_table' => $stockItemTableName],
                ['sku', 'is_salable']
            )->joinLeft(
                ['t' => $subSelect],
                't.sku = main_table.sku',
                $salableQuantityExpression
            )
            ->where('main_table.sku in (?)', $skus)
            ->where('main_table.is_salable = ?', 1);

        $connection = $this->resource->getConnection();

        try {
            if ($connection->isTableExists($stockItemTableName)) {
                return $connection->fetchAll($select) ?: null;
            }

            return null;
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'Could not receive Stock Item data'
            ), $e);
        }
    }
}
