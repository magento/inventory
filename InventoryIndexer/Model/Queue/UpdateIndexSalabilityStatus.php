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
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexProcessor $indexProcessor
     * @param UpdateLegacyStock $updateLegacyStock
     * @param GetParentSkusOfChildrenSkusInterface $getParentSkusByChildrenSkus
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        IndexProcessor $indexProcessor,
        UpdateLegacyStock $updateLegacyStock,
        GetParentSkusOfChildrenSkusInterface $getParentSkusByChildrenSkus
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->indexProcessor = $indexProcessor;
        $this->updateLegacyStock = $updateLegacyStock;
        $this->getParentSkusOfChildrenSkus = $getParentSkusByChildrenSkus;
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
        $stockId = $reservationData->getStock();
        $dataForUpdate = [];
        if ($reservationData->getSkus()) {
            if ($stockId !== $this->defaultStockProvider->getId()) {
                $dataForUpdate = $this->indexProcessor->execute($reservationData, $stockId);
            } else {
                $dataForUpdate = $this->updateLegacyStock->execute($reservationData);
            }

            if ($dataForUpdate) {
                $parentSkusOfChildrenSkus = $this->getParentSkusOfChildrenSkus->execute(array_keys($dataForUpdate));
                if ($parentSkusOfChildrenSkus) {
                    $parentSkus = array_values($parentSkusOfChildrenSkus);
                    $parentSkus = array_merge(...$parentSkus);
                    $parentSkus = array_unique($parentSkus);
                    $parentSkusAffected = array_fill_keys($parentSkus, true);
                    $dataForUpdate = array_merge($dataForUpdate, $parentSkusAffected);
                }
            }
        }

        return $dataForUpdate;
    }
}
