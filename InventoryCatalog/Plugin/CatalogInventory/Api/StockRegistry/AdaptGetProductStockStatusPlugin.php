<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetProductStatusBySku;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getProductStockStatus for multi stocks.
 */
class AdaptGetProductStockStatusPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductStatusBySku
     */
    private $getProductStatusBySku;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetProductStatusBySku $getProductStatusBySku
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductStatusBySku $getProductStatusBySku
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getProductStatusBySku = $getProductStatusBySku;
    }

    /**
     * Get product stock status considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param callable $proceed
     * @param int $productId
     * @param int $scopeId
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductStockStatus(
        StockRegistryInterface $subject,
        callable $proceed,
        $productId,
        $scopeId = null
    ): int {
        $websiteCode = null === $scopeId
            ? $this->storeManager->getWebsite()->getCode()
            : $this->storeManager->getWebsite($scopeId)->getCode();
        $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
        $status = $this->getProductStatusBySku->execute($sku);

        return $status === Status::STATUS_ENABLED ? (int)$this->isProductSalable->execute($sku, $stockId) : 0;
    }
}
