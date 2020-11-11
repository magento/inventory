<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\InventoryCatalog;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Plugin implements compatibility for `all_items_salable` flag on storefront.
 * All not-storefront operations with flag  `all_items_salable` were placed in
 * @see \Magento\InventoryBundleProduct\Plugin\InventorySales\IsBundleProductSalable
 */
class IsBundleProductSalable
{
    /**
     * If flag `all_items_salable` set for Bundle product, return flag value.
     *
     * @param IsProductSalable $subject
     * @param \Closure $proceed
     * @param Product $product
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsProductSalable $subject, \Closure $proceed, Product $product): bool
    {
        if ($product->getTypeId() !== Type::TYPE_CODE) {
            return $proceed($product);
        }

        if ($product->hasData('all_items_salable')) {
            return $product->getData('all_items_salable');
        }

        $isSalable = $proceed($product);
        $product->setData('all_items_salable', $isSalable);

        return $isSalable;
    }
}
