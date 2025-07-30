<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class ProductDataMapperPlugin
{
    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StoreRepositoryInterface $storeRepository
     * @param GetStockItemsDataInterface $getStockItemsData
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        private readonly StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly GetStockItemsDataInterface $getStockItemsData,
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
    }

    /**
     * Map more attributes
     *
     * @param ProductDataMapper $subject
     * @param array|mixed $documents
     * @param mixed $documentData
     * @param mixed $storeId
     * @return array
     * @throws NoSuchEntityException|\Magento\Framework\Exception\LocalizedException
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
        $skus = $this->getSkusByProductIds->execute(array_keys($documents));
        $stockItems = $this->getStockItemsData->execute(array_values($skus), $stock->getStockId());
        foreach ($documents as $productId => $document) {
            $sku = $skus[$productId];
            $document['is_out_of_stock'] = (int)!($stockItems[$sku][GetStockItemsDataInterface::IS_SALABLE] ?? 0);
            $documents[$productId] = $document;
        }

        return $documents;
    }
}
