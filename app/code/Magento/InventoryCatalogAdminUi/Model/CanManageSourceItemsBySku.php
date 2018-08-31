<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;

/**
 * Check source items should be managed for given product sku
 */
class CanManageSourceItemsBySku
{
    /**
     * Provides default stock id for current website in order to get correct stock configuration for product.
     *
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * Provides stock item configuration for given product sku.
     *
     * @var GetStockConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @param GetStockConfigurationInterface $stockConfiguration
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockConfigurationInterface $stockConfiguration,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getStockItemConfiguration = $stockConfiguration;
    }

    /**
     * @param string $sku Sku can be null if product is new
     * @return bool
     */
    public function execute(string $sku = null): bool
    {
        $globalConfiguration = $this->getStockItemConfiguration->forGlobal();
        if (null !== $sku) {
            $stockId = $this->defaultStockProvider->getId();
            $itemConfiguration = $this->getStockItemConfiguration->forStockItem($sku, $stockId);

            return $itemConfiguration->isManageStock() !== null
                ? $itemConfiguration->isManageStock()
                : $globalConfiguration->isManageStock();
        }

        return $globalConfiguration->isManageStock();
    }
}
