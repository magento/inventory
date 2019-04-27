<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface;

/**
 * Get nearby sources of a given zip code, based on the given radius in KM.
 *
 * @api
 */
interface GetNearbyPickupLocationsInterface
{
    /**
     * @param AddressInterface $address
     * @param int $radius
     * @param int $stockId
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[]
     */
    public function execute(AddressInterface $address, int $radius, int $stockId): array;
}
