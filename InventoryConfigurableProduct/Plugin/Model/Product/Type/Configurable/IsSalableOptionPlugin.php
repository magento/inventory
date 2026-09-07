<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
        if ($products === []) {
            return $products;
        }

        $skusToCheck = array_unique(array_map(static function ($product) {
            return $product->getSku();
        }, $products));

        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        $salableResults = $this->areProductsSalable->execute($skusToCheck, $stock->getStockId());
        $this->filterProducts($products, $salableResults);

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
