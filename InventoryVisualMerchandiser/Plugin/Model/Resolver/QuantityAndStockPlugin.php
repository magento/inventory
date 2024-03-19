<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryVisualMerchandiser\Plugin\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VisualMerchandiser\Model\Resolver\QuantityAndStock;

/**
 * This plugin adds multi-source stock calculation capabilities to the Visual Merchandiser feature.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuantityAndStockPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Extend Visual Merchandiser collection with multi-sourcing capabilities.
     *
     * @param QuantityAndStock $subject
     * @param callable $proceed
     * @param Collection $collection
     * @return Collection
     * @throws LocalizedException|\Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundJoinStock(QuantityAndStock $subject, callable $proceed, Collection $collection): Collection
    {
        if ($collection->getStoreId() !== null) {
            $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        } else {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        }

        if ($websiteCode === 'admin') {
            $productLinkField = $this->metadataPool->getMetadata(ProductInterface::class)
                ->getLinkField();
            $collection->joinField(
                'parent_stock',
                $this->resource->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
                null,
                'sku = sku',
                [],
                'left'
            );
            $collection->joinField(
                'child_relation',
                $this->resource->getTableName('catalog_product_relation'),
                null,
                'parent_id = ' . $productLinkField,
                null,
                'left'
            );
            $collection->getSelect()
                ->joinLeft(
                    ['child_product' => $this->resource->getTableName('catalog_product_entity')],
                    'at_child_relation.child_id = child_product.entity_id',
                    []
                )
                ->joinLeft(
                    ['child_stock' => $this->resource->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM)],
                    'child_stock.sku = child_product.sku',
                    []
                );

            $subSelect = clone($collection->getSelect());
            $subSelect->columns(
                [
                    'SUM(IFNULL(at_parent_stock.quantity, 0)) as parent_stock',
                    'SUM(IFNULL(child_stock.quantity, 0)) as child_stock'
                ]
            );
            $subSelect->group(['e.entity_id', 'at_parent_stock.source_code']);

            $collection->getSelect()->reset();
            $collection->getSelect()->from(['e' => $subSelect]);
            $collection->getSelect()->columns(
                new \Zend_Db_Expr(
                    'IF(
                        SUM(IFNULL(parent_stock, 0)) = 0,
                        SUM(IFNULL(child_stock, 0)),
                        SUM(IFNULL(parent_stock, 0))
                    )  AS stock'
                )
            );
        } else {
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $stockId = (int)$stock->getStockId();
            $collection->getSelect()->joinLeft(
                ['inventory_stock' => $this->stockIndexTableNameResolver->execute($stockId)],
                'inventory_stock.sku = e.sku',
                ['stock' => 'IFNULL(quantity, 0)']
            );
        }

        return $collection;
    }
}
