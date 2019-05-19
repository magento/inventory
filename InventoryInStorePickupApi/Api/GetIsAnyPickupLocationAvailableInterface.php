<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Provide information if at least one Pickup Location available for Sales Channel.
 *
 * @api
 */
interface GetIsAnyPickupLocationAvailableInterface
{
    /**
     * @param SalesChannelInterface $salesChannel
     *
     * @return bool
     */
    public function execute(SalesChannelInterface $salesChannel): bool;
}