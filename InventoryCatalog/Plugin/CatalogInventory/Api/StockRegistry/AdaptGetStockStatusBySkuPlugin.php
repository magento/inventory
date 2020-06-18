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
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getStockStatusBySku for multi stocks.
 */
class AdaptGetStockStatusBySkuPlugin
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

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
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        ProductRepositoryInterface $productRepository
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->productRepository = $productRepository;
    }

    /**
     * Get product stock status by sku considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param StockStatusInterface $stockStatus
     * @param string $productSku
     * @param int $scopeId
     * @return StockStatusInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStockStatusBySku(
        StockRegistryInterface $subject,
        StockStatusInterface $stockStatus,
        $productSku,
        $scopeId = null
    ): StockStatusInterface {
        $website = $this->storeManager->getWebsite($scopeId);
        $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode())
            ->getStockId();
        try {
            $qty = $this->getProductSalableQty->execute($productSku, $stockId);
        } catch (InputException $e) {
            $qty = 0;
        }
        $product = $website->getDefaultStore()
            ? $this->productRepository->get($productSku, false, (int)$website->getDefaultStore()->getId())
            : $this->productRepository->get($productSku);
        $stockStatus->setStockStatus((int)$product->isAvailable());
        $stockStatus->setQty($qty);

        return $stockStatus;
    }
}
