<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

/**
 * Remove all reservations without matching order
 */
class FilterExistingOrders
{
    /**
     * Remove all reservations without matching order
     *
     * @param SalableQuantityInconsistency[] $inconsistencies
     * @return SalableQuantityInconsistency[]
     */
    public function execute(array $inconsistencies): array
    {
        return array_filter(
            $inconsistencies,
            function (SalableQuantityInconsistency $inconsistency) {
                return $inconsistency->hasAssignedOrder();
            }
        );
    }
}
