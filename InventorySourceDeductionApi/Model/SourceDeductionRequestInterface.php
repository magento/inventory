<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Request products in a given Qty, sourceCode and StockId
 *
 * @api
 */
interface SourceDeductionRequestInterface
{
    /**
     * Returns the source code associated with the deduction request.
     *
     * @return string
     */
    public function getSourceCode(): string;

    /**
     * Retrieves an array of items to be deducted from the inventory.
     *
     * @return ItemToDeductInterface[]
     */
    public function getItems(): array;

    /**
     * Provides the sales channel information for the deduction request.
     *
     * @return SalesChannelInterface
     */
    public function getSalesChannel(): SalesChannelInterface;

    /**
     * Returns the sales event details related to the deduction request.
     *
     * @return SalesEventInterface
     */
    public function getSalesEvent(): SalesEventInterface;
}
