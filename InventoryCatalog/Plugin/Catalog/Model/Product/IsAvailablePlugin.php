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
 * Is product available in multi stock environment plugin.
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
     * Fetches is salable status from multi-stock and sets it to product is_salable flag.
     *
     * @param Product $product
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeIsAvailable(Product $product): void
    {
        $product->setData('is_salable', $this->isProductSalable->execute($product));
    }
}
