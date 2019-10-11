<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Model;

/**
 * Service which provides info if order is placed using In-Store Pickup delivery.
 *
 * @api
 */
interface IsStorePickupOrderInterface
{
    /**
     * Check if order with the specified id was places with store-pickup.
     *
     * @param int $orderId
     * @return bool
     */
    public function execute(int $orderId): bool;
}
