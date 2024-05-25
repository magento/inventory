<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearchBundleProduct\Model\CatalogSearch\Indexer;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogSearch\Model\Indexer\SelectModifierInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Filter bundle products by enabled child products stock status.
 */
class BundleChildStockStatusModifier implements SelectModifierInterface
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
     * @var StockStatusResource
     */
    private $stockStatusResource;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param StockStatusResource $stockStatusResource
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        StoreRepositoryInterface $storeRepository,
        StockStatusResource $stockStatusResource
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->storeRepository = $storeRepository;
        $this->stockStatusResource = $stockStatusResource;
    }

    /**
     * @inheritdoc
     */
    public function modify(Select $select, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $optionsAvailabilitySelect = $connection->select()->from(
            ['bundle_options' => $this->resourceConnection->getTableName('catalog_product_bundle_option')],
            []
        )->joinInner(
            ['bundle_selections' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
            'bundle_selections.option_id = bundle_options.option_id',
            []
        )->joinInner(
            // table alias must be "e" for joining the stock status
            ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'e.entity_id = bundle_selections.product_id',
            []
        )->group(
            ['bundle_options.parent_id', 'bundle_options.option_id']
        );

        $statusAttribute = $this->productAttributeRepository->get(ProductInterface::STATUS);
        $optionsAvailabilitySelect->joinLeft(
            ['child_status_global' => $statusAttribute->getBackendTable()],
            "child_status_global.{$linkField} = e.{$linkField}"
            . " AND child_status_global.attribute_id = {$statusAttribute->getAttributeId()}"
            . " AND child_status_global.store_id = 0",
            []
        )->joinLeft(
            ['child_status_store' => $statusAttribute->getBackendTable()],
            "child_status_store.{$linkField} = e.{$linkField}"
            . " AND child_status_store.attribute_id = {$statusAttribute->getAttributeId()}"
            . " AND child_status_store.store_id = {$storeId}",
            []
        );

        $store = $this->storeRepository->getById($storeId);
        $this->stockStatusResource->addStockStatusToSelect($optionsAvailabilitySelect, $store->getWebsite());
        $optionsAvailabilitySelect->joinLeft(
            ['children_website' => $this->resourceConnection->getTableName('catalog_product_website')],
            "e.entity_id = children_website.product_id AND children_website.website_id = " . $store->getWebsiteId(),
            []
        );
        $columns = array_column($optionsAvailabilitySelect->getPart(Select::COLUMNS), 1, 2);
        $isSalableColumn = $columns['is_salable'];

        $optionAvailabilityExpr = sprintf(
            'IFNULL(child_status_store.value, child_status_global.value) != %s AND %s = 1',
            Status::STATUS_DISABLED,
            $isSalableColumn
        );

        $isOptionSalableExpr = new \Zend_Db_Expr('MAX(' . $optionAvailabilityExpr . ')');
        $isRequiredOptionUnsalable = $connection->getCheckSql(
            'required = 1 AND ' . $isOptionSalableExpr . ' = 0',
            '1',
            '0'
        );
        $optionsAvailabilitySelect->columns([
            'parent_id' => 'bundle_options.parent_id',
            'required' => 'bundle_options.required',
            'is_available' => $isOptionSalableExpr,
            'is_required_and_unavailable' => $isRequiredOptionUnsalable,
            'child_website_id' => new \Zend_Db_Expr('IFNULL(children_website.website_id, -1)')
        ]);
        $isBundleAvailableExpr = new \Zend_Db_Expr(
            '(
                MAX(is_available) = 1 AND
                MAX(is_required_and_unavailable) = 0 AND
                MIN(child_website_id = ' . $store->getWebsiteId() . ' OR (child_website_id = -1 AND required = 0)) = 1
                )'
        );
        $websiteFilteredOptions = $connection->select();
        $websiteFilteredOptions->reset();
        $websiteFilteredOptions->from($optionsAvailabilitySelect)
            ->where("(required = 0 AND child_website_id > 0) OR required = 1");
        $bundleAvailabilitySelect = $connection->select()
            ->from($websiteFilteredOptions, ['parent_id' => 'parent_id', 'is_available' => $isBundleAvailableExpr])
            ->group('parent_id');

        $existsSelect = $connection->select()
            ->from($bundleAvailabilitySelect, [new \Zend_Db_Expr('1')])
            ->where('is_available = 1')
            ->where("parent_id = e.{$linkField}");
        $typeBundle = Type::TYPE_CODE;
        $select->where(
            "e.type_id != '{$typeBundle}' OR EXISTS ({$existsSelect->assemble()})"
        );
    }
}
