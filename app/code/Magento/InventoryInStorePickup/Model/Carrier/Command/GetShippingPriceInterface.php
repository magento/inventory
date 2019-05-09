<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier\Command;

use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Get shipping price for In-Store Pickup delivery (Service Provider Interface - SPI).
 * Handling fee is not included.
 * @api
 */
interface GetShippingPriceInterface
{
    /**
     * @param RateRequest $request
     * @param float $basePrice
     * @param float $freeBoxes
     * @return float|null
     */
    public function execute(RateRequest $request, float $basePrice, float $freeBoxes): ?float;
}