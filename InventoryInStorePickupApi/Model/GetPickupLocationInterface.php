<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Sugar Service to provide single Pickup Location by Sales Channel Code, Type and Pickup Location Code.
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
     * @return PickupLocationInterface
     * @throws NoSuchEntityException
     */
    public function execute(
        string $pickupLocationCode,
        string $salesChannelType,
        string $salesChannelCode
    ): PickupLocationInterface;
}
