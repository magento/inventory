<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Just an utility service to avoid copy-pasting
 * Extracts pickup_location_code from the order entity
 */
class GetPickupLocationCode
{
    /**
     * @param OrderInterface $order
     *
     * @return string|null
     */
    public function execute(OrderInterface $order): ?string
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes) {
            return $extensionAttributes->getPickupLocationCode();
        }

        return null;
    }
}
