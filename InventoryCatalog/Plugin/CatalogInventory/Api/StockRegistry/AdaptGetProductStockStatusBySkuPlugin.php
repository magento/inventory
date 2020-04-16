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
use Magento\InventoryCatalog\Model\GetProductStatusForStock;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getProductStockStatusBySku for multi stocks.
 */
class AdaptGetProductStockStatusBySkuPlugin
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductStatusForStock
     */
    private $getProductStatusForStock;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetProductStatusForStock $getProductStatusForStock
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductStatusForStock $getProductStatusForStock
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getProductStatusForStock = $getProductStatusForStock;
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
        $status = $this->getProductStatusForStock->execute($productSku, $stockId);
        if ($status === Status::STATUS_DISABLED) {
            return 0;
        }
        $result = $this->areProductsSalable->execute([$productSku], $stockId);
        $result = current($result);

        return (int)$result->isSalable();
    }
}
