<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\SiblingSelectBuilderInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

class SelectBuilder implements SiblingSelectBuilderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @inheritdoc
     */
    public function getSelect(IndexName $indexName, array $skuList = []): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $indexTableName = $this->indexNameResolver->resolveName($indexName);
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $select = $connection->select()
            ->from(
                ['stock' => $indexTableName],
                [
                    IndexStructure::SKU => 'parent_product_entity.sku',
                    IndexStructure::QUANTITY => 'SUM(stock.quantity)',
                    IndexStructure::IS_SALABLE => 'IF(inventory_stock_item.is_in_stock = 0, 0, MAX(stock.is_salable))',
                ]
            )->joinInner(
                ['product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'product_entity.sku = stock.sku',
                []
            )->joinInner(
                ['parent_link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                'parent_link.product_id = product_entity.entity_id',
                []
            )->joinInner(
                ['parent_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'parent_product_entity.' . $linkField . ' = parent_link.parent_id',
                []
            )->joinLeft(
                ['inventory_stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'inventory_stock_item.product_id = parent_product_entity.entity_id'
                . ' AND inventory_stock_item.stock_id = ' . $this->defaultStockProvider->getId(),
                []
            )
            ->group(['parent_product_entity.sku']);

        if ($skuList) {
            $select->where('parent_product_entity.sku IN (?)', $skuList);
        }

        return $select;
    }
}
