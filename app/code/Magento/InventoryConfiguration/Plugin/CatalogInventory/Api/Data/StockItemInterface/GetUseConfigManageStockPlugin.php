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
 * Adapt "use config manage stock" value for stock item configuration.
 */
class GetUseConfigManageStockPlugin
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
     * @return bool
     * @throws NoSuchEntityException
     */
    public function aroundGetUseConfigManageStock(StockItemInterface $subject, \Closure $proceed): bool
    {
        $productId = $subject->getProductId();
        if (!$productId) {
            return (bool)$proceed();
        }

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId()
        );

        return $stockItemConfiguration->isManageStock() === null;
    }
}
