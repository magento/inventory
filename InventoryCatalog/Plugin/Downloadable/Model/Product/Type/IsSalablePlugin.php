<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Downloadable\Model\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Product\Type;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Apply the inventory is-salable result to the according method of the product type model.
 */
class IsSalablePlugin
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
     * @param Type $subject
     * @param \Closure $proceed
     * @param Product $product
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsSalable(Type $subject, \Closure $proceed, Product $product): bool
    {
        return $subject->hasLinks($product) && $this->isProductSalable->execute($product);
    }
}
