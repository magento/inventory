<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Verify configurable options are salable.
 */
class IsSalableOptionPlugin
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
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Remove not salable configurable options from options array.
     *
     * @param Configurable $subject
     * @param array $products
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetUsedProducts(Configurable $subject, array $products): array
    {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        foreach ($products as $key => $product) {
            if (!$this->isProductSalable->execute($product->getSku(), $stock->getStockId())) {
                $product->setIsSalable(0);
                if (!$this->stockConfiguration->isShowOutOfStock()) {
                    unset($products[$key]);
                }
            }
        }

        return $products;
    }
}
