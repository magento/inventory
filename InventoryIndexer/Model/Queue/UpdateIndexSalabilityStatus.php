<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\Exception\StateException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\DefaultStockProcessor;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;

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
     * @var DefaultStockProcessor
     */
    private $defaultStockProcessor;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexProcessor $indexProcessor
     * @param DefaultStockProcessor $defaultStockProcessor
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        IndexProcessor $indexProcessor,
        DefaultStockProcessor $defaultStockProcessor
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->indexProcessor = $indexProcessor;
        $this->defaultStockProcessor = $defaultStockProcessor;
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
                $dataForUpdate = $this->defaultStockProcessor->execute($reservationData);
            }
        }

        return $dataForUpdate;
    }
}
