<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Filter products by stock status
 */
class FilterProductByStock
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var array
     */
    private $selectModifiersPool;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ResourceConnection $resourceConnection
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StoreRepositoryInterface $storeRepository
     * @param MetadataPool $metadataPool
     * @param array $selectModifiersPool
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        ResourceConnection $resourceConnection,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StoreRepositoryInterface $storeRepository,
        MetadataPool $metadataPool,
        array $selectModifiersPool = []
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->resourceConnection = $resourceConnection;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->storeRepository = $storeRepository;
        $this->metadataPool = $metadataPool;
        $this->selectModifiersPool = $selectModifiersPool;
    }

    /**
     * Return filtered product by stock status for product indexer
     *
     * @param array $products
     * @param int $storeId
     * @return array
     */
    public function execute(array $products, int $storeId): array
    {
        $store = $this->storeRepository->getById($storeId);
        $stock = $this->stockByWebsiteIdResolver->execute((int)$store->getWebsiteId());
        $stockId = $stock->getStockId();
        if ($this->defaultStockProvider->getId() === $stockId) {
            return $products;
        }

        $stockTable = $this->stockIndexTableNameResolver->execute($stockId);
        $productIds = array_column($products, 'entity_id');
        $filteredProductIds = $this->getStockStatusesFromCustomStock($productIds, $stockTable);
        return array_filter($products, function ($product) use ($filteredProductIds) {
            return in_array($product['entity_id'], $filteredProductIds);
        });
    }

    /**
     * Get product stock statuses on custom stock.
     *
     * @param array $productIds
     * @param string $stockTable
     * @return array
     */
    private function getStockStatusesFromCustomStock(array $productIds, string $stockTable): array
    {
        $connection = $this->resourceConnection->getConnection();
        if (!$connection->isTableExists($stockTable)) {
            return [];
        }

        $select = $connection->select();
        $select->from(
            ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
            ['entity_id']
        );
        $select->joinInner(
            ['stock' => $stockTable],
            'product.sku = stock.sku',
            []
        );
        $select->where('product.entity_id IN (?)', $productIds);
        $select->where('stock.is_salable = ?', 1);
        $this->applySelectModifiers($select, $stockTable);
        return $connection->fetchCol($select);
    }

    /**
     * Applying filters to select via select modifiers
     *
     * @param Select $select
     * @param string $stockTable
     * @return void
     */
    private function applySelectModifiers(Select $select, string $stockTable): void
    {
        foreach ($this->selectModifiersPool as $selectModifier) {
            $selectModifier->modify($select, $stockTable);
        }
    }
}
