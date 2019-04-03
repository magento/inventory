<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

class IsDisplayReadyForPickupButton
{
    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function execute(\Magento\Sales\Model\Order $order): bool
    {
        return $order->getExtensionAttributes()->getPickupLocationCode()
            && $order->canShip();
    }
}
