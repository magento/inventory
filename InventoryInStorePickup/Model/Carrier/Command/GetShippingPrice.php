<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier\Command;

use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * @inheritdoc
 */
class GetShippingPrice implements GetShippingPriceInterface
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(RateRequest $request, float $basePrice, float $freeBoxes): ?float
    {
        return $basePrice;
    }
}
