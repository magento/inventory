<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Block\Checkout\Onepage;

use Magento\Checkout\Block\Onepage\Success as SuccessBlock;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;

/**
 * Store pickup checkout success block.
 */
class Success extends SuccessBlock
{
    /**
     * Get is order has pick in store delivery method.
     *
     * @return bool
     */
    public function isOrderStorePickup(): bool
    {
        $result = false;
        $order = $this->_checkoutSession->getLastRealOrder();
        if ($order->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
            $result = true;
        }

        return $result;
    }
}
