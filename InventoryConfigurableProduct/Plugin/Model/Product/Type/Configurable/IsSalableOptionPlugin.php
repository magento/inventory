<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetUsedProducts(Configurable $subject, array $products): array
    {
        // Use associative array for fast SKU lookup
        $salableSkus = array_flip(array_map(function ($status) {
            return $status->getSku();
        }, $this->productsSalableStatuses));

        // Collect SKUs not already in $this->productsSalableStatuses
        $skus = array_filter(array_map(function ($product) use ($salableSkus) {
            $sku = $product->getSku();
            return isset($salableSkus[$sku]) ? null : $sku; // Return null if SKU exists, SKU otherwise
        }, $products));

        // If there are no new SKUs to process, filter products and return
        if (empty($skus)) {
            $this->filterProducts($products, $this->productsSalableStatuses);
            return $products;
        }

        // Only now do we need the website and stock information
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        // Update products salable statuses with new salable information
        $this->productsSalableStatuses = array_merge(
            $this->productsSalableStatuses,
            $this->areProductsSalable->execute($skus, $stock->getStockId())
        );

        // Filter products once all updates are made
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
    private function filterProducts(array &$products, array $isSalableResults) : void
    {
        // Transform $isSalableResults into an associative array with SKU as the key
        $salabilityBySku = [];
        foreach ($isSalableResults as $result) {
            $salabilityBySku[$result->getSku()] = $result->isSalable();
        }

        foreach ($products as $key => $product) {
            $sku = $product->getSku();

            // Check if the SKU exists in the salability results and if it's not salable
            if (isset($salabilityBySku[$sku]) && !$salabilityBySku[$sku]) {
                $product->setIsSalable(0);
                if (!$this->stockConfiguration->isShowOutOfStock()) {
                    unset($products[$key]);
                }
            }
        }
    }
}
