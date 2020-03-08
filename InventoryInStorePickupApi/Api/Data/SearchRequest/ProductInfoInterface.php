<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoExtensionInterface;

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
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ProductInfoExtensionInterface;

    /**
     * Set Product Info Extension.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoExtensionInterface $extension
     * @return void
     */
    public function setExtensionAttributes(ProductInfoExtensionInterface $extension): void;
}
