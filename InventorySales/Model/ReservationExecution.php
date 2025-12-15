<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

/**
 * Defer inventory reservation for synchronous orders.
 */
class ReservationExecution implements ReservationExecutionInterface
{
    /**
     * Always defer placing inventory reservation.
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return true;
    }
}
