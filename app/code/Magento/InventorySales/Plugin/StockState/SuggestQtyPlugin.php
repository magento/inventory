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
use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
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
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        GetInventoryConfigurationInterface $getInventoryConfiguration
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->getInventoryConfiguration = $getInventoryConfiguration;
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

            $qtyIncrements = $this->getInventoryConfiguration->getQtyIncrements($productSku, $stockId);
            $isManageStock = $this->getInventoryConfiguration->isManageStock($productSku, $stockId);


            if ($qty <= 0 || $isManageStock === false || $qtyIncrements < 2) {
                throw new LocalizedException(__('Wrong condition.'));
            }

            $minQty = max(
                $this->getInventoryConfiguration->getMinSaleQty($productSku, $stockId),
                $qtyIncrements
            );
            $divisibleMin = ceil($minQty / $qtyIncrements) * $qtyIncrements;
            $maxQty = min(
                $this->getProductSalableQty->execute($productSku, $stockId),
                $this->getInventoryConfiguration->getMaxSaleQty($productSku, $stockId)
            );
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
}
