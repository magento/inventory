<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

/**
 * Get Pickup Locations for requested Sales Channel, ordered by corresponded Source priority.
 *
 * @api
 */
interface GetPickupLocationsAssignedToSalesChannelInterface
{
    /**
     * @param string $salesChannelType
     * @param string $salesChannelCode
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $salesChannelType, string $salesChannelCode): array;
}
