<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Api;

/**
 * Send an email to the customer that order is ready to be picked up.
 *
 * @api
 */
interface NotifyOrdersAreReadyForPickupInterface
{
    /**
     * Notify customer that the orders is ready for pickup.
     *
     * @param int[] $orderIds
     * @return \Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface
     */
    public function execute(array $orderIds): \Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface;
}
