<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\InventoryCatalog\Model\IsProductSalable;

class AfterGetIsSaleable
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
     * @param Product $product
     *
     * @return bool
     */
    public function aroundGetIsSalable(Product $product): bool
    {
        return $this->isProductSalable->execute($product);
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function aroundIsSalable(Product $product): bool
    {
        return $this->isProductSalable->execute($product);
    }
}
