<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use Magento\InventoryIndexer\Indexer\SelectBuilderInterface;

/**
 * Get configurable product for given stock select builder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SelectBuilder implements SelectBuilderInterface
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
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool,
        DefaultStockProviderInterface $defaultStockProvider,
        Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Prepare select.
     *
     * @param int $stockId
     * @return Select
     * @throws Exception
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

        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $statusAttributeId = $this->getAttribute(ProductInterface::STATUS)->getId();

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
            )->joinInner(
                ['product_status' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                "product_entity.$linkField = product_status.$linkField"
                . " AND product_status.attribute_id = $statusAttributeId"
                . ' AND product_status.value = ' . ProductStatus::STATUS_ENABLED,
                []
            )->group(['parent_product_entity.sku']);

        return $select;
    }

    /**
     * Retrieve catalog_product attribute instance by attribute code
     *
     * @param string $attributeCode
     * @return Attribute
     */
    private function getAttribute($attributeCode): Attribute
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
    }
}
