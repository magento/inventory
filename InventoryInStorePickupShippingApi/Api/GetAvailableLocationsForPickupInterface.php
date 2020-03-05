<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Api;

use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestInterface;

/**
 * Provide list of Pickup Location codes which are satisfy conditions of request.
 * @api
 */
interface GetAvailableLocationsForPickupInterface
{
    /**
     * Get Pickup Location codes which can be used for pickup.
     *
     * @param RequestInterface $request
     *
     * @return string[]
     */
    public function execute(RequestInterface $request): array;
}
