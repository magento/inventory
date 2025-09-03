<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

/**
 * DTO used as the type for values of `$items` array passed to PlaceReservationsForSalesEventInterface::execute()
 * @see \Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface
 *
 * @api
 */
interface ItemToSellInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Gets the SKU for the item to sell, returning it as a string value.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Gets the quantity for the item to sell, returning it as a floating-point value.
     *
     * @return float
     */
    public function getQuantity(): float;

    /**
     * Sets the SKU for the item to sell, specifying it as a string value.
     *
     * @param string $sku
     * @return void
     */
    public function setSku(string $sku): void;

    /**
     * Sets the quantity for the item to sell, specifying the amount as a floating-point value.
     *
     * @param float $qty
     * @return void
     */
    public function setQuantity(float $qty): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventorySalesApi\Api\Data\ItemToSellExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magento\InventorySalesApi\Api\Data\ItemToSellExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventorySalesApi\Api\Data\ItemToSellExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySalesApi\Api\Data\ItemToSellExtensionInterface $extensionAttributes
    ): void;
}
