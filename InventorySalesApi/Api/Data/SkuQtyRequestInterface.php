<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents 'are products salable for requested qty' request interface.
 *
 * @api
 */
interface SkuQtyRequestInterface extends ExtensibleDataInterface
{
    /**
     * Retrieve product sku.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Retrieve product quantity.
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set extension attributes to result.
     *
     * @param \Magento\InventorySalesApi\Api\Data\SkuQtyRequestExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        SkuQtyRequestExtensionInterface $extensionAttributes
    ): void;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\InventorySalesApi\Api\Data\SkuQtyRequestExtensionInterface|null
     */
    public function getExtensionAttributes(): ?SkuQtyRequestExtensionInterface;
}
