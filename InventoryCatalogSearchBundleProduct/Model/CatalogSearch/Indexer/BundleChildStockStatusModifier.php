<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearchBundleProduct\Model\CatalogSearch\Indexer;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogSearch\Model\Indexer\SelectModifierInterface;

/**
 * Filter bundle products by enabled child products stock status.
 */
class BundleChildStockStatusModifier implements SelectModifierInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Config $eavConfig
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Config $eavConfig,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection
    ) {
        $this->eavConfig = $eavConfig;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Add stock item filter to select
     *
     * @param Select $select
     * @param string $stockTable
     * @return void
     */
    public function modify(Select $select, string $stockTable): void
    {
        $connection = $this->resourceConnection->getConnection();
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $statusAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'status');
        $existsSelect = $connection->select()->from(
            ['product_link_bundle' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
            [new \Zend_Db_Expr('1')]
        )->where(
            "product_link_bundle.parent_product_id = e.{$linkField}"
        );
        $existsSelect->join(
            ['bundle_product_child' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'bundle_product_child.entity_id = product_link_bundle.product_id',
            []
        );

        $existsSelect->join(
            ['child_product_status' => $this->resourceConnection->getTableName($statusAttribute->getBackendTable())],
            "bundle_product_child.{$linkField} = child_product_status.{$linkField} AND "
            . "child_product_status.attribute_id = " . $statusAttribute->getId(),
            []
        )->where('child_product_status.value = 1');

        $existsSelect->join(
            ['stock_status_index_child' => $stockTable],
            'bundle_product_child.sku = stock_status_index_child.sku',
            []
        )->where('stock_status_index_child.is_salable = 1');
        $typeBundle = Type::TYPE_CODE;
        $select->where(
            "e.type_id != '{$typeBundle}' OR EXISTS ({$existsSelect->assemble()})"
        );
    }
}
