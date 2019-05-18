<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

/**
 * Get Pickup Locations for requested scope, ordered by corresponded Source sort priority.
 *
 * @api
 */
interface GetPickupLocationsAssignedToStockOrderedByPriorityInterface
{
    /**
     * @param int $stockId
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[]
     */
    public function execute(int $stockId): array;
}
