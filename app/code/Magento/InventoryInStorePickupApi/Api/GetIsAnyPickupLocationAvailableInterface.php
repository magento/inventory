<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

/**
 * Provide information if at least one Pickup Location available for Sales Channel.
 *
 * @api
 */
interface GetIsAnyPickupLocationAvailableInterface
{
    /**
     * Provide information if at least one Pickup Location available for Sales Channel.
     *
     * @param string $salesChannelType
     * @param string $salesChannelCode
     * @return bool
     */
    public function execute(string $salesChannelType, string $salesChannelCode): bool;
}
