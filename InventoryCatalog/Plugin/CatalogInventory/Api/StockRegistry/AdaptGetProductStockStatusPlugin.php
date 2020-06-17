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
 * Adapt getProductStockStatus for multi stocks.
 */
class AdaptGetProductStockStatusPlugin
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
     * Get product stock status considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param callable $proceed
     * @param int $productId
     * @param int $scopeId
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException|LocalizedException
     */
    public function aroundGetProductStockStatus(
        StockRegistryInterface $subject,
        callable $proceed,
        $productId,
        $scopeId = null
    ): int {
        $storeId = null === $scopeId
            ? $this->storeManager->getWebsite()->getDefaultStore()->getId()
            : $this->storeManager->getWebsite($scopeId)->getDefaultStore()->getId();

        $product = $this->productRepository->getById($productId, false, (int)$storeId);

        return (int)$product->isAvailable();
    }
}
