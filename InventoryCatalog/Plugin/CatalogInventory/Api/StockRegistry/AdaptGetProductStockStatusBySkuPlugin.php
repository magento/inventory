<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getProductStockStatusBySku for multi stocks.
 */
class AdaptGetProductStockStatusBySkuPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Get product stock status by sku considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param callable $proceed
     * @param string $productSku
     * @param int $scopeId
     * @return int
     * @throws NoSuchEntityException|LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductStockStatusBySku(
        StockRegistryInterface $subject,
        callable $proceed,
        $productSku,
        $scopeId = null
    ): int {
        $storeId = null === $scopeId
            ? $this->storeManager->getWebsite()->getDefaultStore()->getId()
            : $this->storeManager->getWebsite($scopeId)->getDefaultStore()->getId();

        $product = $this->productRepository->get($productSku, false, (int)$storeId);

        return (int)$product->isAvailable();
    }
}
