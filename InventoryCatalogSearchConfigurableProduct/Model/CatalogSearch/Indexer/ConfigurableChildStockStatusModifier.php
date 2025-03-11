<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearchConfigurableProduct\Model\CatalogSearch\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Model\ResourceModel\StockStatusFilterInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogSearch\Model\Indexer\SelectModifierInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Filter configurable products by enabled child products stock status.
 */
class ConfigurableChildStockStatusModifier implements SelectModifierInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var StockStatusFilterInterface
     */
    private $stockStatusFilter;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param StockStatusFilterInterface $stockStatusFilter
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        StoreRepositoryInterface $storeRepository,
        StockStatusFilterInterface $stockStatusFilter
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->storeRepository = $storeRepository;
        $this->stockStatusFilter = $stockStatusFilter;
    }

    /**
     * @inheritdoc
     */
    public function modify(Select $select, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $existsSelect = $connection->select()->from(
            ['product_link_configurable' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            [new \Zend_Db_Expr('1')]
        )->where(
            "product_link_configurable.parent_id = e.{$linkField}"
        );
        $existsSelect->join(
            ['product_child' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'product_child.entity_id = product_link_configurable.product_id',
            []
        );

        $statusAttribute = $this->productAttributeRepository->get(ProductInterface::STATUS);
        $existsSelect->joinLeft(
            ['child_status_global' => $statusAttribute->getBackendTable()],
            "child_status_global.{$linkField} = product_child.{$linkField}"
            . " AND child_status_global.attribute_id = {$statusAttribute->getAttributeId()}"
            . " AND child_status_global.store_id = 0",
            []
        )->joinLeft(
            ['child_status_store' => $statusAttribute->getBackendTable()],
            "child_status_store.{$linkField} = product_child.{$linkField}"
            . " AND child_status_store.attribute_id = {$statusAttribute->getAttributeId()}"
            . " AND child_status_store.store_id = {$storeId}",
            []
        )->where(
            'IFNULL(child_status_store.value, child_status_global.value) != ' . Status::STATUS_DISABLED
        );

        $store = $this->storeRepository->getById($storeId);
        $this->stockStatusFilter->execute(
            $existsSelect,
            'product_child',
            StockStatusFilterInterface::TABLE_ALIAS,
            (int) $store->getWebsiteId()
        );

        $typeConfigurable = Configurable::TYPE_CODE;
        $select->where(
            "e.type_id != '{$typeConfigurable}' OR EXISTS ({$existsSelect->assemble()})"
        );
    }
}
