<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\ResourceModel\Indexer;

use Magento\Bundle\Model\ResourceModel\Indexer\Stock;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\Stock\PrepareReservationsIndexData;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;

class StockPlugin
{
    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ReservationsIndexTable $reservationsIndexTable
     * @param PrepareReservationsIndexData $prepareReservationsIndexData
     */
    public function __construct(
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly ReservationsIndexTable $reservationsIndexTable,
        private readonly PrepareReservationsIndexData $prepareReservationsIndexData,
    ) {
    }

    /**
     * Prepare reservations index table for the default stock reindex process.
     *
     * @param Stock $subject
     * @param callable $proceed
     * @param array|int $entityIds
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundReindexEntity(Stock $subject, callable $proceed, $entityIds = []): void
    {
        $stockId = $this->defaultStockProvider->getId();
        $this->reservationsIndexTable->createTable($stockId);
        $this->prepareReservationsIndexData->execute($stockId);
        $proceed($entityIds);
        $this->reservationsIndexTable->dropTable($stockId);
    }
}
