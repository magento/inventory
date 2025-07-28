<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Model;

/**
 * Is allowed management of source items for specific product sku
 *
 * @api
 */
interface IsSourceItemManagementAllowedForSkuInterface
{
    /**
     * Return whether Source Item management allowed for given SKU
     *
     * @param string $sku
     * @return bool
     */
    public function execute(string $sku): bool;
}
