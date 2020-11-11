<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Apply the inventory get is salable result to the according method of the product type model.
 */
class GetIsSalablePlugin
{
    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @param IsProductSalable $isProductSalable
     */
    public function __construct(
        IsProductSalable $isProductSalable
    ) {
        $this->isProductSalable = $isProductSalable;
    }

    /**
     * Fetches is salable status for multi-stock environment.
     *
     * @param Product $product
     * @param \Closure $proceed
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetIsSalable(Product $product, \Closure $proceed): bool
    {
        if ($product->hasData('is_saleable')) {
            return (bool)$product->getData('is_saleable');
        }
        return $this->isProductSalable->execute($product);
    }
}
