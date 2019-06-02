<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface;

/**
 * Find nearest Pickup Locations by requested address, radius, and affiliation to stock.
 *
 * @api
 */
interface GetNearbyPickupLocationsInterface
{
    /**
     * @param AddressInterface $address
     * @param int $radius
     * @param string $salesChannelType
     * @param string $salesChannelCode
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[]
     */
    public function execute(
        AddressInterface $address,
        int $radius,
        string $salesChannelType,
        string $salesChannelCode
    ): array;
}
