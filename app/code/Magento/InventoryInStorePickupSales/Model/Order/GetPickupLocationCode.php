<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Utility service to avoid copy-pasting.
 *
 * Extracts pickup_location_code from the order entity.
 */
class GetPickupLocationCode
{
    /**
     * Extract pickup location code from order.
     *
     * @param OrderInterface $order
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
