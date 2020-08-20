<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

/**
 * Update legacy stock status for given reservation.
 */
class UpdateLegacyStock
{
    /**
     * @var GetSalabilityDataForUpdate
     */
    private $getSalabilityDataForUpdate;
    /**
     * @var UpdateLegacyStockStatus
     */
    private $updateLegacyStockStatus;

    /**
     * @param GetSalabilityDataForUpdate $getSalabilityDataForUpdate
     * @param UpdateLegacyStockStatus $updateLegacyStockStatus
     */
    public function __construct(
        GetSalabilityDataForUpdate $getSalabilityDataForUpdate,
        UpdateLegacyStockStatus $updateLegacyStockStatus
    ) {
        $this->getSalabilityDataForUpdate = $getSalabilityDataForUpdate;
        $this->updateLegacyStockStatus = $updateLegacyStockStatus;
    }

    /**
     * Update legacy stock status for given reservation.
     *
     * @param ReservationData $reservationData
     * @return bool[]
     */
    public function execute(ReservationData $reservationData): array
    {
        $dataForUpdate = $this->getSalabilityDataForUpdate->execute($reservationData);
        $this->updateLegacyStockStatus->execute($dataForUpdate);

        return $dataForUpdate;
    }
}
