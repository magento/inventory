<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class ProductDataMapperPlugin
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StoreRepositoryInterface $storeRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StoreRepositoryInterface $storeRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->storeRepository = $storeRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Map more attributes
     *
     * @param ProductDataMapper $subject
     * @param array|mixed $documents
     * @param mixed $documentData
     * @param mixed $storeId
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMap(
        ProductDataMapper $subject,
        array $documents,
        array $documentData,
        int $storeId
    ): array {
        $store = $this->storeRepository->getById($storeId);
        $stock = $this->stockByWebsiteIdResolver->execute((int)$store->getWebsiteId());

        try {
            $skus = $this->getSkusByProductIds->execute(array_keys($documents));
        } catch (NoSuchEntityException $e) {
            $skus = [];
        }

        $productsSaleability = $this->getStockStatuses($skus, $stock->getStockId());

        foreach ($documents as $productId => $document) {
            $sku = $document['sku'] ?? '';
            $document['is_out_of_stock'] = !$sku ? 1 : (int)($productsSaleability[$sku] ?? 1);
            $documents[$productId] = $document;
        }

        return $documents;
    }

    /**
     * Get product stock statuses on stock.
     *
     * @param array $skus
     * @param int $stockId
     * @return array
     */
    private function getStockStatuses(array $skus, int $stockId): array
    {
        $stockTable = $this->stockIndexTableNameResolver->execute($stockId);
        $connection = $this->resourceConnection->getConnection();
        if (!$connection->isTableExists($stockTable)) {
            return [];
        }
        $select = $connection->select();
        $select->from(
            [$this->resourceConnection->getTableName($stockTable)],
            ['sku', 'is_salable']
        );
        $select->where('sku IN (?)', $skus);

        return $connection->fetchPairs($select) ?? [];
    }
}
