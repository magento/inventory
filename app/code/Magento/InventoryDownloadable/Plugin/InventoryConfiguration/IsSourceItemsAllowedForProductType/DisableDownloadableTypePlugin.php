<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDownloadable\Plugin\InventoryConfiguration\IsSourceItemsAllowedForProductType;

use Magento\Downloadable\Model\Product\Type;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;

/**
 * Disable Source items management for downloadable product type
 */
class DisableDownloadableTypePlugin
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
        if ($productType === Type::TYPE_DOWNLOADABLE) {
            return false;
        }

        return $proceed($productType);
    }
}
