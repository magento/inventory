<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command;

use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Get number of packages with free delivery.
 *
 * @api
 */
interface GetFreePackagesInterface
{
    /**
     * Get number of packages with free delivery.
     *
     * @param RateRequest $request
     * @return float
     */
    public function execute(RateRequest $request): float;
}
