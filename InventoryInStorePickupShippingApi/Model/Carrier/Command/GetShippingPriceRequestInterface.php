<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command;

use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestExtensionInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Create Shipping Price Request Data Transfer Object.
 *
 * @api
 */
interface GetShippingPriceRequestInterface
{
    /**
     * Create Shipping Price Request Data Transfer Object.
     *
     * @param RateRequest $rateRequest
     * @param float $defaultPrice
     * @param float $freePackages
     * @param ShippingPriceRequestExtensionInterface|null $shippingPriceRequestExtension
     * @return ShippingPriceRequestInterface
     */
    public function execute(
        RateRequest $rateRequest,
        float $defaultPrice,
        float $freePackages,
        ?ShippingPriceRequestExtensionInterface $shippingPriceRequestExtension = null
    ): ShippingPriceRequestInterface;
}
