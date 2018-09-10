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
 * Adapt "use config backorders" value for stock item configuration.
 */
class GetUseConfigBackordersPlugin
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
     * @return bool
     * @throws NoSuchEntityException
     */
    public function aroundGetUseConfigBackorders(StockItemInterface $subject, \Closure $proceed): bool
    {
        $productId = $subject->getProductId();
        if (!$productId) {
            return (bool)$proceed();
        }

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $stockItemConfiguration = $this->getSourceConfiguration->forSourceItem(
            $productSku,
            $this->defaultSourceProvider->getCode()
        );

        return $stockItemConfiguration->getBackorders() === null;
    }
}
