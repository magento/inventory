<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class SuggestQtyPlugin
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
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        GetStockConfigurationInterface $getStockItemConfiguration
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->getStockConfiguration = $getStockItemConfiguration;
    }

    /**
     * @param StockStateInterface $subject
     * @param \Closure $proceed
     * @param int $productId
     * @param float $qty
     * @param int|null $scopeId
     * @return float
     *
     * @return float
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSuggestQty(
        StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $qty,
        $scopeId
    ): float {
        try {
            $skus = $this->getSkusByProductIds->execute([$productId]);
            $productSku = $skus[$productId];

            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $stockId = (int)$stock->getStockId();

            $qtyIncrements = $this->getQtyIncrements($productSku, $stockId);
            $isManageStock = $this->getIsMangeStock($productSku, $stockId);
            $minSaleQty = $this->getMinSaleQty($productSku, $stockId);
            $maxSaleQty = $this->getMaxSaleQty($productSku, $stockId);

            if ($qty <= 0 || $isManageStock === false || $qtyIncrements < 2) {
                throw new LocalizedException(__('Wrong condition.'));
            }

            $minQty = max($minSaleQty, $qtyIncrements);
            $divisibleMin = ceil($minQty / $qtyIncrements) * $qtyIncrements;
            $maxQty = min($this->getProductSalableQty->execute($productSku, $stockId), $maxSaleQty);
            $divisibleMax = floor($maxQty / $qtyIncrements) * $qtyIncrements;

            if ($qty < $minQty || $qty > $maxQty || $divisibleMin > $divisibleMax) {
                throw new LocalizedException(__('Wrong condition.'));
            }

            $closestDivisibleLeft = floor($qty / $qtyIncrements) * $qtyIncrements;
            $closestDivisibleRight = $closestDivisibleLeft + $qtyIncrements;
            $acceptableLeft = min(max($divisibleMin, $closestDivisibleLeft), $divisibleMax);
            $acceptableRight = max(min($divisibleMax, $closestDivisibleRight), $divisibleMin);

            return abs($acceptableLeft - $qty) < abs($acceptableRight - $qty) ? $acceptableLeft : $acceptableRight;
        } catch (LocalizedException $e) {
            return $qty;
        }
    }

    /**
     * @param $productSku
     * @param int $stockId
     * @return float
     */
    private function getQtyIncrements($productSku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($productSku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->getQtyIncrements() !== null
            ? $stockConfiguration->getQtyIncrements()
            : $globalConfiguration->getQtyIncrements();

        return $stockItemConfiguration->getQtyIncrements() !== null
            ? $stockItemConfiguration->getQtyIncrements()
            : $defaultValue;
    }

    /**
     * @param $productSku
     * @param int $stockId
     * @return bool
     */
    private function getIsMangeStock($productSku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($productSku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->isManageStock() !== null
            ? $stockConfiguration->isManageStock()
            : $globalConfiguration->isManageStock();

        return $stockItemConfiguration->isManageStock() !== null
            ? $stockItemConfiguration->isManageStock()
            : $defaultValue;
    }

    /**
     * @param $productSku
     * @param int $stockId
     * @return float
     */
    private function getMinSaleQty($productSku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($productSku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->getMinSaleQty() !== null
            ? $stockConfiguration->getMinSaleQty()
            : $globalConfiguration->getMinSaleQty();

        return $stockItemConfiguration->getMinSaleQty() !== null
            ? $stockItemConfiguration->getMinSaleQty()
            : $defaultValue;
    }

    /**
     * @param $productSku
     * @param int $stockId
     * @return float
     */
    private function getMaxSaleQty($productSku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($productSku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->getMaxSaleQty() !== null
            ? $stockConfiguration->getMaxSaleQty()
            : $globalConfiguration->getMaxSaleQty();

        return $stockItemConfiguration->getMaxSaleQty() !== null
            ? $stockItemConfiguration->getMaxSaleQty()
            : $defaultValue;
    }
}
