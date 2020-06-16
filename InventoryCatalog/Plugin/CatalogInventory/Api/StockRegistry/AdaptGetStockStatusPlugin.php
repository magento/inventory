<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getStockStatus for multi stocks
 */
class AdaptGetStockStatusPlugin
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        ProductRepositoryInterface $productRepository
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->productRepository = $productRepository;
    }

    /**
     * Set stock status to product considering multi stock.
     *
     * @param StockRegistryInterface $subject
     * @param StockStatusInterface $stockStatus
     * @param int $productId
     * @param int $scopeId
     * @return StockStatusInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStockStatus(
        StockRegistryInterface $subject,
        StockStatusInterface $stockStatus,
        $productId,
        $scopeId = null
    ): StockStatusInterface {
        $websiteCode = null === $scopeId
            ? $this->storeManager->getWebsite()->getCode()
            : $this->storeManager->getWebsite($scopeId)->getCode();
        $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
        $product = $this->productRepository->get($sku);

        try {
            $qty = $this->getProductSalableQty->execute($sku, $stockId);
        } catch (InputException $e) {
            $qty = 0;
        }

        $stockStatus->setStockStatus((int)$product->isAvailable());
        $stockStatus->setQty($qty);

        return $stockStatus;
    }
}
