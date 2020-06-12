<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Apply the inventory is-available result to the according method of the product type model.
 */
class IsAvailablePlugin
{
    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @var ProductHelper
     */
    private $product;

    /**
     * @param IsProductSalable $isProductSalable
     * @param ProductHelper $product
     */
    public function __construct(
        IsProductSalable $isProductSalable,
        ProductHelper $product
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->product = $product;
    }

    /**
     * Fetches is salable status from multi-stock.
     *
     * @param Product $product
     * @param \Closure $proceed
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsAvailable(Product $product, \Closure $proceed): bool
    {
        if ($this->product->getSkipSaleableCheck()) {
            return true;
        }

        return $this->isProductSalable->execute($product);
    }
}
