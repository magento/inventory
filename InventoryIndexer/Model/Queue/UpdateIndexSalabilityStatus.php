<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\Exception\StateException;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;

/**
 * Recalculates index items salability status.
 */
class UpdateIndexSalabilityStatus
{
    /**
     * @param IndexProcessor $indexProcessor
     */
    public function __construct(
        private readonly IndexProcessor $indexProcessor,
    ) {
    }

    /**
     * Reindex items salability statuses.
     *
     * @param ReservationData $reservationData
     * @return array<string, bool> - ['sku' => bool]: list of SKUs with salability status changed.
     * @throws StateException
     */
    public function execute(ReservationData $reservationData): array
    {
        $dataForUpdate = [];
        if ($reservationData->getSkus()) {
            $dataForUpdate = $this->indexProcessor->execute($reservationData);
        }

        return $dataForUpdate;
    }
}
