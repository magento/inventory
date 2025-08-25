<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\InventoryBundleProduct\Model\GetProductSelection;
use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * Get bundle product stock status service.
 */
class GetBundleProductStockStatus
{
    /**
     * Cache for stock, for scenario where same product is in multiple options.
     *
     * @var array
     */
    protected array $stockCache = [];

    /**
     * @param GetProductSelection $getProductSelection
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        protected GetProductSelection $getProductSelection,
        protected IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        protected GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
    }

    /**
     * Provides bundle product stock status.
     *
     * @param ProductInterface $product
     * @param OptionInterface[] $bundleOptions
     * @param int $stockId
     *
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(ProductInterface $product, array $bundleOptions, int $stockId): bool
    {
        //get non processed bundle product sku.
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($product->getDataByKey('sku'), $stockId);
        if (!$stockItemConfiguration->getExtensionAttributes()->getIsInStock()) {
            return false;
        }

        $requiredOptions = [];
        $optionalOptions = [];
        foreach ($bundleOptions as $option) {
            if ($option->getRequired()) {
                $requiredOptions[] = $option;
            } else {
                $optionalOptions[] = $option;
            }
        }

        if (!empty($requiredOptions)) {
            return $this->checkRequiredOptions($product, $requiredOptions, $stockId);
        } else {
            return $this->checkOptionalOptions($product, $optionalOptions, $stockId);
        }
    }

    /**
     * Check stock for a bundle that has required options.
     *
     * In this scenario, we need to ensure that all required options have at least one in-stock selection.
     * If any required option has no in-stock selections, the bundle is not salable.
     * We don't need to consider optional components in this case.
     *
     * @param ProductInterface $product
     * @param OptionInterface[] $bundleOptions
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    private function checkRequiredOptions(ProductInterface $product, array $bundleOptions, int $stockId): bool
    {
        $isSalable = false;
        foreach ($bundleOptions as $option) {
            $hasSalable = false;

            $bundleSelections = $this->getProductSelection->execute($product, $option);
            foreach ($bundleSelections->getItems() as $result) {
                $qty = $this->getRequestedQty($result, $stockId);
                if ($this->isSaleableForSkuAndQuantity((string)$result->getSku(), $stockId, $qty)) {
                    $hasSalable = true;
                    break;
                }
            }

            if ($hasSalable) {
                $isSalable = true;
            }

            if (!$hasSalable && $option->getRequired()) {
                $isSalable = false;
                break;
            }
        }

        return $isSalable;
    }

    /**
     * Check the stock for a bundle with no required options.
     *
     * In this scenario, we only need to ensure that at least one selection from any optional option is in stock.
     * If any optional option has at least one in-stock selection, the bundle is salable.
     *
     * @param ProductInterface $product
     * @param OptionInterface[] $bundleOptions
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    private function checkOptionalOptions(ProductInterface $product, array $bundleOptions, int $stockId): bool
    {
        $isSalable = false;
        foreach ($bundleOptions as $option) {
            $bundleSelections = $this->getProductSelection->execute($product, $option);
            foreach ($bundleSelections->getItems() as $result) {
                $qty = $this->getRequestedQty($result, $stockId);
                if ($this->isSaleableForSkuAndQuantity((string)$result->getSku(), $stockId, $qty)) {
                    $isSalable = true;
                    break 2;
                }
            }
        }

        return $isSalable;
    }

    /**
     * Check if a product is saleable for a given SKU and quantity.
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qty
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    private function isSaleableForSkuAndQuantity($sku, int $stockId, float $qty): bool
    {
        $cacheKey = implode('~', [$sku, $stockId, $qty]);
        if (isset($this->stockCache[$cacheKey])) {
            return $this->stockCache[$cacheKey];
        }

        $result = $this->isProductSalableForRequestedQty->execute(
            $sku,
            $stockId,
            $qty
        );
        $this->stockCache[$cacheKey] = $result->isSalable();
        return $result->isSalable();
    }

    /**
     * Get bundle product selection qty.
     *
     * @param Product $product
     * @param int $stockId
     * @return float
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    private function getRequestedQty(Product $product, int $stockId): float
    {
        if ((int)$product->getSelectionCanChangeQty()) {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute((string)$product->getSku(), $stockId);
            return $stockItemConfiguration->getMinSaleQty();
        }

        return (float) $product->getSelectionQty();
    }
}
