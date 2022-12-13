<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

class ProductDataMapperPlugin
{
    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StoreRepositoryInterface $storeRepository
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StoreRepositoryInterface $storeRepository,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->storeRepository = $storeRepository;
        $this->getStockItemData = $getStockItemData;
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

        foreach ($documents as $productId => $document) {
            $sku = $document['sku'] ?? '';
            if (!$sku) {
                $document['is_out_of_stock'] = 1;
            } else {
                try {
                    $stockItemData = $this->getStockItemData->execute($sku, $stock->getStockId());
                } catch (NoSuchEntityException $e) {
                    $stockItemData = null;
                }
                $document['is_out_of_stock'] = null !== $stockItemData
                    ? (int)$stockItemData[GetStockItemDataInterface::IS_SALABLE] : 1;
            }
            $documents[$productId] = $document;
        }

        return $documents;
    }
}
