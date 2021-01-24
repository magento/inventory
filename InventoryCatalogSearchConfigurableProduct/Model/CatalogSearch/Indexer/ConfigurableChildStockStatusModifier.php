<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearchConfigurableProduct\Model\CatalogSearch\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogSearch\Model\Indexer\SelectModifierInterface;

/**
 * Filter configurable products by enabled child products stock status.
 */
class ConfigurableChildStockStatusModifier implements SelectModifierInterface
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
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        MetadataPool $metadataPool,
        Config $eavConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
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
            ['product_link_configurable' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            [new \Zend_Db_Expr('1')]
        )->where(
            "product_link_configurable.parent_id = product.{$linkField}"
        );
        $existsSelect->join(
            ['product_child' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'product_child.entity_id = product_link_configurable.product_id',
            []
        );

        $existsSelect->join(
            ['child_product_status' => $this->resourceConnection->getTableName($statusAttribute->getBackendTable())],
            "product_child.{$linkField} = child_product_status.{$linkField} AND "
            . "child_product_status.attribute_id = " . $statusAttribute->getId(),
            []
        )->where('child_product_status.value = 1');

        $existsSelect->join(
            ['stock_status_index_child' => $stockTable],
            'product_child.sku = stock_status_index_child.sku',
            []
        )->where('stock_status_index_child.is_salable = 1');
        $typeConfigurable = Configurable::TYPE_CODE;
        $select->where(
            "product.type_id != '{$typeConfigurable}' OR EXISTS ({$existsSelect->assemble()})"
        );
    }
}
