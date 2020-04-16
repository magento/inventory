<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Pricing\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Verify configurable options are salable.
 */
class IsSalableOptionPlugin
{
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
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockConfigurationInterface $stockConfiguration
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockConfigurationInterface $stockConfiguration,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockConfiguration = $stockConfiguration;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Remove not salable configurable options from options array.
     *
     * @param ConfigurableOptionsProviderInterface $subject
     * @param array $products
     * @param ProductInterface $product
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProducts(
        ConfigurableOptionsProviderInterface $subject,
        array $products,
        ProductInterface $product
    ) : array {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }
        $salableResults = $this->areProductsSalable->execute($skus, $stock->getStockId());

        foreach ($products as $key => $product) {
            foreach ($salableResults as $result) {
                if ($result->getSku() === $product->getSku() && !$result->isSalable()) {
                    $product->setIsSalable(0);
                    if (!$this->stockConfiguration->isShowOutOfStock()) {
                        unset($products[$key]);
                    }
                    break;
                }
            }
        }

        return $products;
    }
}
