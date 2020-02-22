<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Data Transfer Object for request to get available pickup locations for pickup.
 * @see \Magento\InventoryInStorePickupShippingApi\Api\GetAvailableLocationsForPickupInterface
 */
interface RequestInterface extends ExtensibleDataInterface
{
    /**
     * Set products SKU.
     *
     * @param string[] $products
     * @return void
     */
    public function setProductsSku(array $products): void;

    /**
     * Get products SKU.
     *
     * @return string[]
     */
    public function getProductsSku(): array;

    /**
     * Get extension attributes.
     *
     * @return \Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface
     */
    public function getExtensionAttributes(): RequestExtensionInterface;

    /**
     * Set extension attributes.
     *
     * @param \Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface $requestExtension
     * @return void
     */
    public function setExtensionAttributes(RequestExtensionInterface $requestExtension): void;
}
