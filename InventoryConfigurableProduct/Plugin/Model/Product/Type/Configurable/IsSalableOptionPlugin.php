<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Verify configurable options are salable.
 */
class IsSalableOptionPlugin
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
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var IsProductSalableResultInterface[]
     */
    private $productsSalableStatuses = [];

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->areProductsSalable = $areProductsSalable;
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
        $skus = [];
        foreach ($products as $product) {
            foreach ($this->productsSalableStatuses as $isProductSalableResult) {
                if ($isProductSalableResult->getSku() === $product->getSku()) {
                    continue 2;
                }
            }
            $skus[] = $product->getSku();
        }

        if (empty($skus)) {
            $this->filterProducts($products, $this->productsSalableStatuses);
            return $products;
        }

        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        $this->productsSalableStatuses = array_merge(
            $this->productsSalableStatuses,
            $this->areProductsSalable->execute($skus, $stock->getStockId())
        );
        $this->filterProducts($products, $this->productsSalableStatuses);
        return $products;
    }

    /**
     * Filter products according to the salability results.
     *
     * @param array $products
     * @param array $isSalableResults
     * @return void
     */
    private function filterProducts(array $products, array $isSalableResults) : void
    {
        foreach ($products as $key => $product) {
            foreach ($isSalableResults as $result) {
                if ($result->getSku() === $product->getSku() && !$result->isSalable()) {
                    $product->setIsSalable(0);
                    if (!$this->stockConfiguration->isShowOutOfStock()) {
                        unset($products[$key]);
                    }
                }
            }
        }
    }
}
