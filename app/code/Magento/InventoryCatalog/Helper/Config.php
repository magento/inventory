<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryCatalog\Helper;

/**
 * InventoryCatalog data helper
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Config paths for using throughout the code
     */
    const XML_PATH_SYNCHRONIZE_INVENTORY_ON_CHECKOUT = 'inventory/inventory_legacy/synchronize_inventory_on_checkout';

    /**
     * Whether Legacy Inventory should be synchonized on Checkout
     *
     * @return bool
     */
    public function isLegacyInventorySynchronizedOnCheckout(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SYNCHRONIZE_INVENTORY_ON_CHECKOUT);
    }
}