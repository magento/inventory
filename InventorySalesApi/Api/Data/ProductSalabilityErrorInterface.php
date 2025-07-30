<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

/**
 * @api
 */
interface ProductSalabilityErrorInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Gets the error code related to product salability, returning it as a string value.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Gets the error message related to product salability, returning it as a string value.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ProductSalabilityErrorExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorExtensionInterface $extensionAttributes
    ): void;
}
