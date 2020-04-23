<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command;

use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Get shipping price for In-Store Pickup delivery.
 *
 * @api
 */
interface GetShippingPriceInterface
{
    /**
     * Get shipping price for In-Store Pickup delivery.
     *
     * @param RateRequest $rateRequest
     * @return float|null
     */
    public function execute(RateRequest $rateRequest): ?float;
}
