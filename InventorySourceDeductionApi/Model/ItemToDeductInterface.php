<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

/**
 * Represents requested quantity for particular product
 *
 * @api
 */
interface ItemToDeductInterface
{
    /**
     * Requested SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Requested Product Quantity
     *
     * @return float
     */
    public function getQty(): float;
}
