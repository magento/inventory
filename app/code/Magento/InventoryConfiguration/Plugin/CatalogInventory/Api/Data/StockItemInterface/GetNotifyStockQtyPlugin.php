<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Api\Data\StockItemInterface;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;

/**
 * Adapt "notify stock quantity" value for stock item configuration.
 */
class GetNotifyStockQtyPlugin
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param StockItemInterface $subject
     * @param \Closure $proceed
     * @return float
     * @throws NoSuchEntityException
     */
    public function aroundGetNotifyStockQty(StockItemInterface $subject, \Closure $proceed): float
    {
        $productId = $subject->getProductId();
        if (!$productId) {
            return (float)$proceed();
        }

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem(
            $productSku,
            $this->defaultSourceProvider->getCode()
        );
        $sourceConfiguration = $this->getSourceConfiguration->forSource($this->defaultSourceProvider->getCode());
        $globalConfiguration = $this->getSourceConfiguration->forGlobal();
        $defaultValue = $sourceConfiguration->getNotifyStockQty() !== null
            ? $sourceConfiguration->getNotifyStockQty()
            : $globalConfiguration->getNotifyStockQty();

        return $sourceItemConfiguration->getNotifyStockQty() !== null
            ? $sourceItemConfiguration->getNotifyStockQty()
            : $defaultValue;
    }
}
