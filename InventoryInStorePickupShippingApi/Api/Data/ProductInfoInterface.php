<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Product Info Data Transfer Object.
 * @api
 */
interface ProductInfoInterface extends ExtensibleDataInterface
{
    /**
     * Get Product SKU.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Get Product Info Extension.
     *
     * @return \Magento\InventoryInStorePickupShippingApi\Api\Data\ProductInfoExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ProductInfoExtensionInterface;

    /**
     * Set Product Info Extension.
     *
     * @param \Magento\InventoryInStorePickupShippingApi\Api\Data\ProductInfoExtensionInterface $productInfoExtension
     * @return void
     */
    public function setExtensionAttributes(ProductInfoExtensionInterface $productInfoExtension): void;
}
