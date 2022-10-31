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
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
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
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

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
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        DefaultSourceProviderInterface $defaultSourceProvider,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Extend Visual Merchandiser collection with multi-sourcing capabilities.
     *
     * @param QuantityAndStock $subject
     * @param callable $proceed
     * @param Collection $collection
     * @return Collection
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundJoinStock(QuantityAndStock $subject, callable $proceed, Collection $collection): Collection
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();
        if ($stockId === $this->defaultStockProvider->getId()) {
            $defaultCode = $this->defaultSourceProvider->getCode();
            $productLinkField = $this->metadataPool->getMetadata(ProductInterface::class)
                ->getLinkField();
            $collection->joinField(
                'parent_stock',
                $this->resource->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
                null,
                'sku = sku',
                ['source_code' => $defaultCode],
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
                    'child_stock.sku = child_product.sku'
                    . $collection->getConnection()->quoteInto(' AND child_stock.source_code = ?', $defaultCode),
                    []
                )
                ->columns(
                    new \Zend_Db_Expr(
                        'COALESCE( SUM(child_stock.quantity),
                         at_parent_stock.quantity) AS stock'
                    )
                );
        } else {
            $collection->getSelect()->joinLeft(
                ['inventory_stock' => $this->stockIndexTableNameResolver->execute($stockId)],
                'inventory_stock.sku = e.sku',
                ['stock' => 'quantity']
            );
        }

        return $collection;
    }
}
