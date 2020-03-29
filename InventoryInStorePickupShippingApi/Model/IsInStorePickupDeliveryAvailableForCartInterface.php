<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model;

/**
 * Check if In-Store Pickup delivery method is applicable for a cart by cartId.
 *
 * @api
 */
interface IsInStorePickupDeliveryAvailableForCartInterface
{
    /**
     * Check if In-Store Pickup delivery method is applicable for a cart by cartId.
     *
     * @param int $cartId
     *
     * @return bool
     */
    public function execute(int $cartId): bool;
}
