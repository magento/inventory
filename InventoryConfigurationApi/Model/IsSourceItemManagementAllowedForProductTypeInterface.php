<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Model;

/**
 * Is allowed management of source items for specific product type
 *
 * @api
 */
interface IsSourceItemManagementAllowedForProductTypeInterface
{
    /**
     * Checks if source item management is allowed for a given product type and returns a boolean result.
     *
     * @param string $productType
     * @return bool
     */
    public function execute(string $productType): bool;
}
