<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Api\Data\StockItemInterface;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;

/**
 * Adapt "max sale quantity" value for stock item configuration.
 */
class GetMaxSaleQtyPlugin
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @param StockItemInterface $subject
     * @param \Closure $proceed
     * @return float
     * @throws NoSuchEntityException
     */
    public function aroundGetMaxSaleQty(StockItemInterface $subject, \Closure $proceed): float
    {
        $productId = $subject->getProductId();
        if (!$productId) {
            return (float)$proceed();
        }

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId()
        );
        $stockConfiguration = $this->getStockConfiguration->forStock($this->defaultStockProvider->getId());
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $defaultValue = $stockConfiguration->getMaxSaleQty() !== null
            ? $stockConfiguration->getMaxSaleQty()
            : $globalConfiguration->getMaxSaleQty();

        return $stockItemConfiguration->getMaxSaleQty() !== null
            ? $stockItemConfiguration->getMaxSaleQty()
            : $defaultValue;
    }
}
