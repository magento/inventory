<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

/**
 * Represents result of service Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface::execute
 *
 * @api
 */
interface ProductSalableResultInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Checks if the product is salable based on its availability and conditions.
     *
     * @return bool
     */
    public function isSalable(): bool;

    /**
     * Returns a list of errors related to product salability.
     *
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface[]
     */
    public function getErrors(): array;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalableResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ProductSalableResultExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventorySalesApi\Api\Data\ProductSalableResultExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySalesApi\Api\Data\ProductSalableResultExtensionInterface $extensionAttributes
    ): void;
}
