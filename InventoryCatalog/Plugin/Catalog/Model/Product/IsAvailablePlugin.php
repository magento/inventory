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
 * Apply the inventory is-available result to the according method of the product type model.
 */
class IsAvailablePlugin
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
     * Fetches is salable status from multi-stock.
     *
     * @param Product $product
     * @param \Closure $proceed
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsAvailable(Product $product, \Closure $proceed): bool
    {
        return $this->isProductSalable->execute($product);
    }
}
