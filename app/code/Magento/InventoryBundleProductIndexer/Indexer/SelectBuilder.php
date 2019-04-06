<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\Framework\EntityManager\MetadataPool;

class SelectBuilder
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
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
    }

    /**
     * Prepare select.
     *
     * @param int $stockId
     * @return Select
     */
    public function execute(int $stockId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $indexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$stockId)
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        $indexTableName = $this->indexNameResolver->resolveName($indexName);

        $select = $connection->select()
            ->from(
                ['stock' => $indexTableName],
                [
                    IndexStructure::SKU => 'parent_product_entity.sku',
                    IndexStructure::QUANTITY => 'SUM(stock.quantity)',
                    IndexStructure::IS_SALABLE => 'MAX(stock.is_salable)',
                ]
            )->joinInner(
                ['product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'product_entity.sku = stock.sku',
                []
            )->joinInner(
                ['parent_bundle' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
                'parent_bundle.product_id = product_entity.entity_id',
                []
            )->joinInner(
                ['parent_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'parent_product_entity.entity_id = parent_bundle.parent_product_id',
                []
            )
            ->group(['parent_product_entity.sku']);

        return $select;
    }
}
