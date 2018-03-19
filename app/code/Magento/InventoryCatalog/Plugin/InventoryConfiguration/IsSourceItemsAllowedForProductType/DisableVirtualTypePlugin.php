<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryCatalog\Plugin\InventoryConfiguration\IsSourceItemsAllowedForProductType;

use Magento\Catalog\Model\Product\Type;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductType;

/**
 * Disable Source items management for virtual product type
 */
class DisableVirtualTypePlugin
{
    /**
     * @param IsSourceItemsAllowedForProductType $subject
     * @param callable $proceed
     * @param string $productType
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsSourceItemsAllowedForProductType $subject, callable $proceed, string $productType)
    {
        if ($productType === Type::TYPE_VIRTUAL) {
            return false;
        }

        return $proceed($productType);
    }
}
