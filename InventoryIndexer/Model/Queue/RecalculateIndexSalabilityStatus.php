<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

/**
 * Recalculates index items salability status.
 */
class RecalculateIndexSalabilityStatus
{
    /**
     * @param ReservationData $reservationData
     *
     * @return void
     */
    public function execute(ReservationData $reservationData): void
    {
        //TODO
        list($skus, $stock) = [$reservationData->getSkus(), $reservationData->getStock()];
    }
}
