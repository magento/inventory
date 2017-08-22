<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Api;

use Magento\InventoryApi\Api\Data\ReservationInterface;

/**
 * Domain service used to retrieve Reservation by Product SKU and Stock
 *
 * @api
 */
interface GetReservationsForProductInterface
{
    /**
     * Get Reservations for given SKU in a given Stock
     *
     * @param string $sku
     * @param int $stockId
     * @return ReservationInterface[]
     */
    public function execute(string $sku, int $stockId): array;
}
