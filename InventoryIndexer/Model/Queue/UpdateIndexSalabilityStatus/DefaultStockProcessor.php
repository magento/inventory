<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateDefaultStockStatus;

/**
 * Update default stock status for given reservation.
 */
class DefaultStockProcessor
{
    /**
     * @var GetSalabilityDataForUpdate
     */
    private $getSalabilityDataForUpdate;
    /**
     * @var UpdateDefaultStockStatus
     */
    private $updateDefaultStockStatus;

    /**
     * @param GetSalabilityDataForUpdate $getSalabilityDataForUpdate
     * @param UpdateDefaultStockStatus $updateDefaultStockStatus
     */
    public function __construct(
        GetSalabilityDataForUpdate $getSalabilityDataForUpdate,
        UpdateDefaultStockStatus $updateDefaultStockStatus
    ) {
        $this->getSalabilityDataForUpdate = $getSalabilityDataForUpdate;
        $this->updateDefaultStockStatus = $updateDefaultStockStatus;
    }

    /**
     * Update default stock status for given reservation.
     *
     * @param ReservationData $reservationData
     * @return bool[]
     */
    public function execute(ReservationData $reservationData)
    {
        $dataForUpdate = $this->getSalabilityDataForUpdate->execute($reservationData);
        $this->updateDefaultStockStatus->execute($dataForUpdate, ResourceConnection::DEFAULT_CONNECTION);

        return $dataForUpdate;
    }
}
