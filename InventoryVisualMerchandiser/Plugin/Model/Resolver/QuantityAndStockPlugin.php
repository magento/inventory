<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryVisualMerchandiser\Plugin\Model\Resolver;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
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
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
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
            $collection->joinField(
                'parent_stock',
                $this->resource->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
                null,
                'sku = sku',
                null,
                'left'
            );
            $collection->joinField(
                'child_relation',
                $this->resource->getTableName('catalog_product_relation'),
                null,
                'parent_id = entity_id',
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
