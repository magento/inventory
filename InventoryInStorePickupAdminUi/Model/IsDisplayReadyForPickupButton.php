<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Sales\Model\Order;

class IsDisplayReadyForPickupButton
{
    /**
     * @param Order $order
     *
     * @return bool
     */
    public function execute(Order $order): bool
    {
        return $order->getExtensionAttributes()->getPickupLocationCode()
            && $order->canShip();
    }
}
