<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Pricing\Price\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Base select processor.
 */
class BaseStockStatusSelectProcessor implements BaseSelectProcessorInterface
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockConfigurationInterface $stockConfig
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param ResourceConnection $resourceConnection
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockConfigurationInterface $stockConfig,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        ResourceConnection $resourceConnection,
        DefaultStockProviderInterface $defaultStockProvider,
        MetadataPool $metadataPool
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockConfig = $stockConfig;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->resourceConnection = $resourceConnection;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Improves the select with stock status sub query.
     *
     * @param Select $select
     * @return Select
     * @throws NoSuchEntityException
     */
    public function process(Select $select)
    {
        if (!$this->stockConfig->isShowOutOfStock()) {
            return $select;
        }

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();
        if ($stockId === $this->defaultStockProvider->getId()) {
            $select->join(
                ['si' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'si.product_id = l.product_id',
                []
            );
            $select->joinInner(
                ['stock_parent' => $this->stockIndexTableNameResolver->execute($stockId)],
                'stock_parent.sku = le.sku',
                []
            );
            $select->joinLeft(
                ['si_parent' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'si_parent.product_id = l.parent_id',
                []
            );
            $select->where('si.is_in_stock = ?', Stock::STOCK_IN_STOCK);
            $select->orWhere(
                'IFNULL(si_parent.is_in_stock, stock_parent.is_salable) = ?',
                Stock::STOCK_OUT_OF_STOCK
            );
        } else {
            $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $linkField = $metadata->getLinkField();

            $select->joinInner(
                ['le2' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'le2.' . $linkField . ' = l.product_id',
                []
            )->joinInner(
                ['stock' => $this->stockIndexTableNameResolver->execute($stockId)],
                'stock.sku = le2.sku',
                []
            )->joinInner(
                ['stock_parent' => $this->stockIndexTableNameResolver->execute($stockId)],
                'stock_parent.sku = le.sku',
                []
            )->where(
                'stock.is_salable = ?',
                1
            )->orWhere(
                'stock.is_salable = ?',
                0
            )->where(
                'stock_parent.is_salable = ?',
                0
            );
        }

        return $select;
    }
}
