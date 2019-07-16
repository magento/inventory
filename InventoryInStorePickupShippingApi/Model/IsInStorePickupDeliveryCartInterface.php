<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Sugar service to check if Cart has In-Store Pickup Delivery Method.
 * @api
 */
interface IsInStorePickupDeliveryCartInterface
{
    /**
     * Check whether Cart has In-Store Pickup Delivery Method.
     *
     * @param CartInterface $cart
     *
     * @return bool
     */
    public function execute(CartInterface $cart): bool;
}
