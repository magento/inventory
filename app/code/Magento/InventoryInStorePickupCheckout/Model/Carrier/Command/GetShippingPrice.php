<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupCheckout\Model\Carrier\Command;

use Magento\InventoryInStorePickupCheckoutApi\Api\Data\ShippingPriceRequestInterface;
use Magento\InventoryInStorePickupCheckoutApi\Model\Carrier\Command\GetShippingPriceInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * @inheritdoc
 */
class GetShippingPrice implements GetShippingPriceInterface
{
    /**
     * @inheritdoc
     */
    public function execute(ShippingPriceRequestInterface $shippingPriceRequest, RateRequest $rateRequest): float
    {
        if ($shippingPriceRequest->getFreePackages() === (float)$rateRequest->getPackageQty()) {
            return 0.0;
        }

        return $shippingPriceRequest->getDefaultPrice();
    }
}
