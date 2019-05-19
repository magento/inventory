<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupCheckoutApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Shipping Price Request Data Transfer Object.
 */
interface ShippingPriceRequestInterface extends ExtensibleDataInterface
{
    /**
     * Get Default Price of In-Store Pickup Delivery.
     *
     * @return float
     */
    public function getDefaultPrice(): float;

    /**
     * Get count of free boxes.
     *
     * @return float
     */
    public function getFreePackages(): float;

    /**
     * @param ShippingPriceRequestExtensionInterface|null $extensionAttributes
     *
     * @return void
     */
    public function setExtensionAttributes(?ShippingPriceRequestExtensionInterface $extensionAttributes): void;

    /**
     * @return ShippingPriceRequestExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ShippingPriceRequestExtensionInterface;
}
