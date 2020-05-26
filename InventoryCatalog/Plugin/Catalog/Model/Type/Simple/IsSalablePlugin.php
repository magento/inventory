<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Type\Simple;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
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
     * @param Simple $subject
     * @param \Closure $proceed
     * @param Product $product
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsSalable(Simple $subject, \Closure $proceed, Product $product): bool
    {
        return $this->isProductSalable->execute($product);
    }
}
