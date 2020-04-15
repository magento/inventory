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
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getProductStockStatusBySku for multi stocks.
 */
class AdaptGetProductStockStatusBySkuPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

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
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetProductStatusBySku $getProductStatusBySku
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductStatusBySku $getProductStatusBySku
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getProductStatusBySku = $getProductStatusBySku;
    }

    /**
     * Get product stock status considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param callable $proceed
     * @param string $productSku
     * @param int $scopeId
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductStockStatusBySku(
        StockRegistryInterface $subject,
        callable $proceed,
        $productSku,
        $scopeId = null
    ): int {
        $websiteCode = null === $scopeId
            ? $this->storeManager->getWebsite()->getCode()
            : $this->storeManager->getWebsite($scopeId)->getCode();
        $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        $status = $this->getProductStatusBySku->execute($productSku);

        return $status === Status::STATUS_ENABLED ? (int)$this->isProductSalable->execute($productSku, $stockId) : 0;
    }
}
