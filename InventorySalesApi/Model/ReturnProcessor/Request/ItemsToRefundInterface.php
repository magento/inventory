<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model\ReturnProcessor\Request;

/**
 * DTO used as the type for values of `$items` array passed to PlaceReservationsForSalesEventInterface::execute()
 * @see \Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface
 *
 * @api
 */
interface ItemsToRefundInterface
{
    /**
     * Returns the SKU of items to refund for a sales event reservation request.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Returns the quantity of items to refund for a sales event reservation request.
     *
     * @return float
     */
    public function getQuantity(): float;

    /**
     * Returns the processed quantity of items to refund for a sales event reservation request.
     *
     * @return float
     */
    public function getProcessedQuantity(): float;
}
