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
     * Get products SKU.
     *
     * @return \Magento\InventoryInStorePickupShippingApi\Api\Data\ProductInfoInterface[]
     */
    public function getProductsInfo(): array;

    /**
     * Get Sales Channel Type.
     *
     * @return string
     */
    public function getScopeType(): string;

    /**
     * Get Sales Channel code.
     *
     * @return string
     */
    public function getScopeCode(): string;

    /**
     * Get extension attributes.
     *
     * @return \Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface|null
     */
    public function getExtensionAttributes(): ?RequestExtensionInterface;

    /**
     * Set extension attributes.
     *
     * @param \Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface $requestExtension
     * @return void
     */
    public function setExtensionAttributes(RequestExtensionInterface $requestExtension): void;
}
