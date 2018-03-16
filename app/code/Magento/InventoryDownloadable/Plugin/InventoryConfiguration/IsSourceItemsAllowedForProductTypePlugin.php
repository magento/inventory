<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryDownloadable\Plugin\InventoryConfiguration;

use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductType;

/**
 * Class provides after Plugin on
 * Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductType::execute
 * to disable Source items management for downloadable product type
 */
class IsSourceItemsAllowedForProductTypePlugin
{
    /**
     * @param IsSourceItemsAllowedForProductType $subject
     * @param callable $proceed
     * @param string $productType
     * @return bool
     */
    public function aroundExecute(IsSourceItemsAllowedForProductType $subject, callable $proceed, $productType)
    {
        if ($productType === \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return false;
        }

        return $proceed($productType);
    }
}