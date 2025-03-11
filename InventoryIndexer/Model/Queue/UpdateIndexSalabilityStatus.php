<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\Exception\StateException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\UpdateLegacyStock;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;
use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;

/**
 * Recalculates index items salability status.
 */
class UpdateIndexSalabilityStatus
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var IndexProcessor
     */
    private $indexProcessor;
    /**
     * @var UpdateLegacyStock
     */
    private $updateLegacyStock;

    /**
     * @var GetParentSkusOfChildrenSkusInterface
     */
    private $getParentSkusOfChildrenSkus;

    /**
     * @var ReservationDataFactory
     */
    private $reservationDataFactory;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexProcessor $indexProcessor
     * @param UpdateLegacyStock $updateLegacyStock
     * @param GetParentSkusOfChildrenSkusInterface $getParentSkusByChildrenSkus
     * @param ReservationDataFactory $reservationDataFactory
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        IndexProcessor $indexProcessor,
        UpdateLegacyStock $updateLegacyStock,
        GetParentSkusOfChildrenSkusInterface $getParentSkusByChildrenSkus,
        ReservationDataFactory $reservationDataFactory
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->indexProcessor = $indexProcessor;
        $this->updateLegacyStock = $updateLegacyStock;
        $this->getParentSkusOfChildrenSkus = $getParentSkusByChildrenSkus;
        $this->reservationDataFactory = $reservationDataFactory;
    }

    /**
     * Reindex items salability statuses.
     *
     * @param ReservationData $reservationData
     *
     * @return bool[] - ['sku' => bool]: list of SKUs with salability status changed.
     * @throws StateException
     */
    public function execute(ReservationData $reservationData): array
    {
        $dataForUpdate = [];
        if ($reservationData->getSkus()) {
            $dataForUpdate = $this->processReservation($reservationData);
            if ($dataForUpdate) {
                $parentSkusOfChildrenSkus = $this->getParentSkusOfChildrenSkus->execute(array_keys($dataForUpdate));
                if ($parentSkusOfChildrenSkus) {
                    $parentSkus = array_values($parentSkusOfChildrenSkus);
                    $parentSkus = array_merge(...$parentSkus);
                    $parentSkus = array_unique($parentSkus);
                    $parentReservationData = $this->reservationDataFactory->create([
                        'skus' => $parentSkus,
                        'stock' => $reservationData->getStock(),
                    ]);
                    $parentDataForUpdate = $this->processReservation($parentReservationData);
                    $dataForUpdate += $parentDataForUpdate + array_fill_keys($parentSkus, true);
                }
            }
        }

        return $dataForUpdate;
    }

    /**
     * Reindex reservation data.
     *
     * @param ReservationData $reservationData
     * @return array
     */
    private function processReservation(ReservationData $reservationData): array
    {
        if ($reservationData->getStock() !== $this->defaultStockProvider->getId()) {
            $dataForUpdate = $this->indexProcessor->execute($reservationData, $reservationData->getStock());
        } else {
            $dataForUpdate = $this->updateLegacyStock->execute($reservationData);
        }

        return $dataForUpdate;
    }
}
