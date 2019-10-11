<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Model;

/**
 * Service to check if order is ready to be picked up by customer at the Pickup Location.
 *
 * @api
 */
interface IsOrderReadyForPickupInterface
{
    /**
     * Check if order is ready to be picked up by customer at the pickup location.
     *
     * @param int $orderId
     * @return bool
     */
    public function execute(int $orderId): bool;
}
