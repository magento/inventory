<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Sales\Model\Order;

/**
 * Check if 'Notify Order is Ready for Pickup' button should be rendered
 */
class IsDisplayReadyForPickupButton
{
    /**
     * @param Order $order
     *
     * @return bool
     */
    public function execute(Order $order): bool
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return false;
        }

        return $extensionAttributes->getPickupLocationCode()
            && $order->canShip();
    }
}
