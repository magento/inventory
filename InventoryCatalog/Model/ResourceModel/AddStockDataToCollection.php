<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Add Stock data to product collection
 */
class AddStockDataToCollection
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider = null
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider ?: ObjectManager::getInstance()
            ->get(DefaultStockProviderInterface::class);
    }

    /**
     * Add Stock data to product collection
     *
     * @param Collection $collection
     * @param bool $isFilterInStock
     * @param int $stockId
     * @return void
     */
    public function execute(Collection $collection, bool $isFilterInStock, int $stockId)
    {
        $resource = $collection->getResource();

        $collection->getSelect()
            ->joinLeft(['inventory_reservation' => $resource->getTable('inventory_reservation')],
                sprintf('%s.sku = inventory_reservation.sku'
                    . ' AND %s.type_id = "%s"'
                    . ' AND "%d" = inventory_reservation.stock_id',
                    Collection::MAIN_TABLE_ALIAS,
                    Collection::MAIN_TABLE_ALIAS,
                    ProductType::TYPE_SIMPLE,
                    $stockId
                ),
                []
            );

        if ($stockId === $this->defaultStockProvider->getId()) {
            $isSalableColumnName = 'stock_status';
            $collection->getSelect()
                ->{$isFilterInStock ? 'join' : 'joinLeft'}(
                    ['stock_status_index' => $resource->getTable('cataloginventory_stock_status')],
                    sprintf('%s.entity_id = stock_status_index.product_id', Collection::MAIN_TABLE_ALIAS),
                    [IndexStructure::IS_SALABLE => 'IF ('
                        . "stock_status_index.$isSalableColumnName"
                        . ' AND (SUM(IFNULL(inventory_reservation.quantity, 0)) + stock_status_index.qty > 0)'
                        . ', 1, 0)'
                    ]
                )->group(sprintf('%s.entity_id', Collection::MAIN_TABLE_ALIAS));
        } else {
            $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stockId);
            $collection->getSelect()->join(
                ['product' => $resource->getTable('catalog_product_entity')],
                sprintf('product.entity_id = %s.entity_id', Collection::MAIN_TABLE_ALIAS),
                []
            );
            $isSalableColumnName = IndexStructure::IS_SALABLE;
            $collection->getSelect()
                ->{$isFilterInStock ? 'join' : 'joinLeft'}(
                    ['stock_status_index' => $stockIndexTableName],
                    'product.sku = stock_status_index.' . IndexStructure::SKU,
                    [$isSalableColumnName => 'IF ('
                        . "stock_status_index.$isSalableColumnName"
                        . ' AND (SUM(IFNULL(inventory_reservation.quantity, 0)) + stock_status_index.quantity > 0)'
                        . ', 1, 0)'
                    ]
                )->group(sprintf('%s.entity_id', Collection::MAIN_TABLE_ALIAS));
        }

        if ($isFilterInStock) {
            $collection->getSelect()
                ->where('stock_status_index.' . $isSalableColumnName . ' = ?', 1);
        }
    }
}
