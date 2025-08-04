<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Api\Data;

/**
 * Specifies item and quantity for partial inventory transfer.
 *
 * @api
 */
interface PartialInventoryTransferItemInterface
{
    public const SKU = 'sku';
    public const QTY = 'qty';

    /**
     * Returns the SKU of the partial inventory transfer item.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Sets the SKU for the partial inventory transfer item.
     *
     * @param string $sku
     */
    public function setSku(string $sku): void;

    /**
     * Returns the quantity of the partial inventory transfer item.
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Sets the quantity for the partial inventory transfer item.
     *
     * @param float $qty
     */
    public function setQty(float $qty): void;
}
