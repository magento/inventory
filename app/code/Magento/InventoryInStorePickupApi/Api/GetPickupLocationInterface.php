<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Get Pickup Location by provided Pickup Location Code.
 *
 * @api
 */
interface GetPickupLocationInterface
{
    /**
     * Get Pickup Location by provided Pickup Location Code.
     *
     * @param string $pickupLocationCode
     * @param string $salesChannelType
     * @param string $salesChannelCode
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface
     * @throws NoSuchEntityException
     */
    public function execute(
        string $pickupLocationCode,
        string $salesChannelType,
        string $salesChannelCode
    ): PickupLocationInterface;
}
