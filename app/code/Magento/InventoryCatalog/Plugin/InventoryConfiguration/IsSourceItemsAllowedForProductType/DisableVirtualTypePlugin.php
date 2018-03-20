<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryCatalog\Plugin\InventoryConfiguration\IsSourceItemsAllowedForProductType;

use Magento\Catalog\Model\Product\Type;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;

/**
 * Disable Source items management for virtual product type
 */
class DisableVirtualTypePlugin
{
    /**
     * @param IsSourceItemsAllowedForProductTypeInterface $subject
     * @param callable $proceed
     * @param string $productType
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IsSourceItemsAllowedForProductTypeInterface $subject,
        callable $proceed,
        string $productType
    ):bool {
        if ($productType === Type::TYPE_VIRTUAL) {
            return false;
        }

        return $proceed($productType);
    }
}
