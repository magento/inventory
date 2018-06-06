<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\EntityManager\MetadataPool;
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
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        MetadataPool $metadataPool
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param Collection $collection
     * @param bool $isFilterInStock
     * @param int $stockId
     * @return void
     */
    public function execute(Collection $collection, bool $isFilterInStock, int $stockId)
    {
        $tableName = $this->stockIndexTableNameResolver->execute($stockId);

        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $resource = $collection->getResource();
        $collection->getSelect()->joinInner(
            ['product' => $resource->getTable('catalog_product_entity')],
            sprintf('product.' . $linkField . ' = %s.' . $linkField, Collection::MAIN_TABLE_ALIAS),
            []
        );
        $collection->getSelect()
            ->join(
                ['stock_status_index' => $tableName],
                'product.sku = stock_status_index.' . IndexStructure::SKU,
                [IndexStructure::IS_SALABLE]
            );

        if ($isFilterInStock) {
            $collection->getSelect()
                ->where('stock_status_index.' . IndexStructure::IS_SALABLE . ' = ?', 1);
        }
    }
}
