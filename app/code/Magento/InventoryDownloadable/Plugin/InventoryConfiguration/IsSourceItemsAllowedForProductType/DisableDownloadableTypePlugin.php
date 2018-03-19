<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDownloadable\Plugin\InventoryConfiguration\IsSourceItemsAllowedForProductType;

use Magento\Downloadable\Model\Product\Type;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductType;

/**
 * Disable Source items management for downloadable product type
 */
class DisableDownloadableTypePlugin
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
        if ($productType === Type::TYPE_DOWNLOADABLE) {
            return false;
        }

        return $proceed($productType);
    }
}
