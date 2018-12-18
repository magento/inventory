<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;

/**
 * @inheritdoc
 */
class GetStockItemData implements GetStockItemDataInterface
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
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): ?array
    {
        $isDefaultStock = $stockId === $this->defaultStockProvider->getId();
        if ($isDefaultStock) {
            $stockItemTableName = $this->resource->getTableName('cataloginventory_stock_status');
            $qtyColumnName = 'qty';
            $isSalableColumnName = 'stock_status';
        } else {
            $stockItemTableName = $this->stockIndexTableNameResolver->execute($stockId);
            $qtyColumnName = IndexStructure::QUANTITY;
            $isSalableColumnName = IndexStructure::IS_SALABLE;
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['main' => $stockItemTableName],
                [
                    GetStockItemDataInterface::QUANTITY => $qtyColumnName,
                    GetStockItemDataInterface::IS_SALABLE => $isSalableColumnName,
                ]
            );
        if ($isDefaultStock) {
            $select->joinInner(
                ['product' => $this->resource->getTableName('catalog_product_entity')],
                'product.entity_id = main.product_id',
                ''
            );
        }
        $select->where(IndexStructure::SKU . ' = ?', $sku);
        try {
            if ($connection->isTableExists($stockItemTableName)) {
                return $connection->fetchRow($select) ?: null;
            }

            return null;
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'Could not receive Stock Item data'
            ), $e);
        }
    }
}
