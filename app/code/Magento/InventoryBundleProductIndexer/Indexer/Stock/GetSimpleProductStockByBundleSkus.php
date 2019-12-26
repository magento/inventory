<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

/**
 * Class GetSimpleProductStockByBundleSkus
 */
class GetSimpleProductStockByBundleSkus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param Collection $bundleProductsCollection
     * @param int $stockId
     * @return array
     * @throws \Exception
     */
    public function execute(Collection $bundleProductsCollection, int $stockId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $indexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        $indexTableName = $this->indexNameResolver->resolveName($indexName);
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $select = $connection->select()
            ->from(
                ['bundle_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                [
                    IndexStructure::SKU => 'simple_product.sku',
                    IndexStructure::QUANTITY => 'MIN(inventory.quantity)',
                    IndexStructure::IS_SALABLE => 'MAX(inventory.is_salable)',
                ]
            )->joinInner(
                ['bundle_selection' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
                'bundle_product.' . $linkField . '=bundle_selection.parent_product_id',
                []
            )->joinInner(
                ['simple_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'bundle_selection.product_id=simple_product.' . $linkField,
                []
            )->joinInner(
                ['inventory' => $indexTableName],
                'inventory.sku=simple_product.sku',
                []
            )->where(
                'bundle_product.sku IN (' . $this->getBundleProductSkusAsStringByCollection($bundleProductsCollection) . ')'
            )->group(['simple_product.sku']);

        $stockData = $connection->fetchAll($select);
        $result = [];
        array_walk($stockData, function (&$value, &$key) use (&$result) {
            $result[$value['sku']] = $value;
        });

        return $result;
    }

    /**
     * Provide list of bundle skus as string for IN statement in sql query
     *
     * @param Collection $bundleProductsCollection
     *
     * @return string
     */
    private function getBundleProductSkusAsStringByCollection(Collection $bundleProductsCollection)
    {
        $skus = [];
        /** @var ProductInterface $product */
        foreach ($bundleProductsCollection as $product) {
            $skus[] = $product->getSku();
        }

        return '\'' . implode('\',\'', $skus) . '\'';
    }
}
