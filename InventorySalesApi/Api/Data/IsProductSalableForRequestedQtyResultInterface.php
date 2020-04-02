<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents "is product salable for requested quantity" result interface.
 *
 * @api
 */
interface IsProductSalableForRequestedQtyResultInterface extends ExtensibleDataInterface
{
    /**
     * Retrieve product sku from result.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Retrieve stock id from result.
     *
     * @return int
     */
    public function getStockId(): int;

    /**
     * Retrieve is salable result.
     *
     * @return bool
     */
    public function isSalable(): bool;

    /**
     * Retrieve errors from result.
     *
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface[]
     */
    public function getErrors(): array;

    /**
     * Set extension attributes to result.
     *
     * @param \Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultExtensionInterface $extAttributes
     * @return void
     */
    public function setExtensionAttributes(
        IsProductSalableForRequestedQtyResultExtensionInterface $extAttributes
    ): void;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?IsProductSalableForRequestedQtyResultExtensionInterface;
}
