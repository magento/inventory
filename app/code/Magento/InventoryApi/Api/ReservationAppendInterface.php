<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Api;

use Magento\InventoryApi\Api\Data\ReservationInterface;

/**
 * Domain service used to appends reservations when order is placed or canceled
 *
 * @api
 */
interface ReservationAppendInterface
{
    /**
     * Append reservations when Order Placed (or Cancelled)
     *
     * @param ReservationInterface[] $reservations
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(array $reservations);
}
