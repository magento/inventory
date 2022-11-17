<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductDataMapperPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        AreProductsSalableInterface $areProductsSalable,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->areProductsSalable = $areProductsSalable;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * Map more attributes
     *
     * @param ProductDataMapper $subject
     * @param array|mixed $documents
     * @param mixed $documentData
     * @param mixed $storeId
     * @param mixed $context
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMap(
        ProductDataMapper $subject,
        array $documents,
        $documentData,
        mixed $storeId,
        $context
    ): array {
        $websiteCode = $this->storeManager->getStore($storeId)->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $skus = $this->getSkusByProductIds->execute(array_keys($documents));

        $productsSaleability = [];
        foreach ($this->areProductsSalable->execute($skus, $stock->getStockId()) as $productStock) {
            $productsSaleability[$productStock->getSku()] = (int)$productStock->isSalable();
        }

        foreach ($documents as $productId => $document) {
            $sku = $document['sku'] ?? '';
            $document['is_out_of_stock'] = !$sku ? 1 : (int)($productsSaleability[$sku] ?? 1);
            $documents[$productId] = $document;
        }

        return $documents;
    }
}
